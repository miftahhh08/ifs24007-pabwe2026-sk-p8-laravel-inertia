<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Exceptions\AiGenerationException;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function fakeClient(array|\Throwable $result): void
    {
        $this->app->instance(AiClientInterface::class, new class($result) implements AiClientInterface {
            public function __construct(private array|\Throwable $result)
            {
            }

            public function generatePosts(string $topic, int $count): array
            {
                if ($this->result instanceof \Throwable) {
                    throw $this->result;
                }

                return $this->result;
            }
        });
    }

    private function fakeAiResponse(string $content, int $status = 200): void
    {
        config([
            'openai.api_key' => 'kunci-uji',
            'openai.base_url' => 'https://ai.test/v1/',
            'openai.model' => 'model-uji',
        ]);

        // Http::fake() bersifat akumulatif (stub pertama menang), jadi mulai dari pabrik HTTP baru.
        Http::swap(new HttpFactory());
        Http::fake(['ai.test/*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status)]);
    }

    private function item(string $title = 'Judul AI'): array
    {
        return ['title' => $title, 'category' => 'Teknologi', 'excerpt' => 'Ringkas', 'content' => 'Isi lengkap'];
    }

    // ---------- Endpoint ----------

    public function test_ai_generate_flashes_drafts(): void
    {
        $this->fakeClient([new GeneratedPostItem('Judul AI', 'Teknologi', 'Ringkas', 'Isi lengkap')]);

        $this->actingAs(User::factory()->create())->from('/dashboard/posts')
            ->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 2])
            ->assertRedirect('/dashboard/posts')
            ->assertSessionHas('ai_drafts', [$this->item()]);
    }

    public function test_ai_generate_validates_input(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/dashboard/posts/ai-generate', [])->assertSessionHasErrors(['topic', 'count']);
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 0])->assertSessionHasErrors('count');
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 6])->assertSessionHasErrors('count');
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 'abc'])->assertSessionHasErrors('count');
    }

    public function test_ai_generate_reports_ai_failure_as_validation_error(): void
    {
        $this->fakeClient(new AiGenerationException('Layanan AI gagal.'));

        $this->actingAs(User::factory()->create())
            ->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 1])
            ->assertSessionHasErrors(['topic' => 'Layanan AI gagal.']);
    }

    public function test_ai_save_stores_selected_drafts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/posts/ai-save', [
            'drafts' => [$this->item('AI Satu'), $this->item('AI Dua')],
        ])->assertRedirect(route('dashboard.posts.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('posts', ['title' => 'AI Satu', 'user_id' => $user->id, 'is_published' => false]);
        $this->assertDatabaseHas('posts', ['title' => 'AI Dua', 'user_id' => $user->id, 'is_published' => false]);
    }

    public function test_ai_save_validates_input(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/dashboard/posts/ai-save', [])->assertSessionHasErrors('drafts');
        $this->post('/dashboard/posts/ai-save', ['drafts' => [['title' => 'X', 'category' => 'Ngawur', 'content' => 'Y']]])
            ->assertSessionHasErrors('drafts.0.category');
    }

    // ---------- Service ----------

    public function test_container_resolves_openai_service(): void
    {
        $this->assertInstanceOf(OpenAiPostGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_service_requires_api_key(): void
    {
        config(['openai.api_key' => null]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('API key');

        app(AiClientInterface::class)->generatePosts('topik', 1);
    }

    public function test_service_parses_plain_json_array_and_sends_correct_request(): void
    {
        $this->fakeAiResponse(json_encode([$this->item('Satu'), $this->item('Dua'), $this->item('Tiga')]));

        $items = app(AiClientInterface::class)->generatePosts('laravel', 2);

        $this->assertCount(2, $items); // dibatasi sesuai jumlah diminta
        $this->assertSame('Satu', $items[0]->title);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://ai.test/v1/chat/completions'
            && $r->hasHeader('Authorization', 'Bearer kunci-uji')
            && $r['model'] === 'model-uji');
    }

    public function test_service_handles_fenced_wrapped_and_object_responses(): void
    {
        $json = json_encode([$this->item()]);
        $service = app(AiClientInterface::class);

        $this->fakeAiResponse("```json\n{$json}\n```");
        $this->assertCount(1, $service->generatePosts('x', 3));

        $this->fakeAiResponse("Berikut hasilnya: {$json} semoga membantu.");
        $this->assertCount(1, $service->generatePosts('x', 3));

        $this->fakeAiResponse(json_encode(['articles' => [$this->item(), $this->item('Lain')]]));
        $this->assertCount(2, $service->generatePosts('x', 3));

        $this->fakeAiResponse(json_encode($this->item()));
        $this->assertCount(1, $service->generatePosts('x', 3));
    }

    public function test_service_skips_invalid_items(): void
    {
        $this->fakeAiResponse(json_encode([
            'bukan array',
            ['title' => 'Tanpa isi', 'content' => ''],
            $this->item('Valid'),
        ]));

        $items = app(AiClientInterface::class)->generatePosts('x', 5);

        $this->assertCount(1, $items);
        $this->assertSame('Valid', $items[0]->title);
    }

    public function test_service_fails_when_no_valid_article_returned(): void
    {
        $service = app(AiClientInterface::class);

        foreach (['tidak ada json sama sekali', '[ukuran besar]', '"hanya teks"', '{"foo":"bar"}', ''] as $content) {
            $this->fakeAiResponse($content);

            try {
                $service->generatePosts('x', 1);
                $this->fail('Seharusnya melempar AiGenerationException untuk: '.$content);
            } catch (AiGenerationException $e) {
                $this->assertStringContainsString('tidak mengembalikan artikel yang valid', $e->getMessage());
            }
        }
    }

    public function test_service_fails_on_http_error(): void
    {
        $this->fakeAiResponse('apa saja', 500);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('HTTP 500');

        app(AiClientInterface::class)->generatePosts('x', 1);
    }

    public function test_service_fails_on_connection_error(): void
    {
        config(['openai.api_key' => 'kunci-uji', 'openai.base_url' => 'https://ai.test/v1']);
        Http::fake(function () {
            throw new ConnectionException('boom');
        });

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Tidak dapat terhubung');

        app(AiClientInterface::class)->generatePosts('x', 1);
    }

    // ---------- DTO ----------

    public function test_generated_post_item_normalizes_data(): void
    {
        $this->assertNull(GeneratedPostItem::fromArray(['title' => ['bukan string'], 'content' => 'x']));
        $this->assertNull(GeneratedPostItem::fromArray(['title' => 'Judul', 'content' => '  ']));

        $item = GeneratedPostItem::fromArray([
            'title' => str_repeat('j', 300),
            'category' => 'ngawur',
            'excerpt' => 123,
            'content' => ' Isi ',
        ]);

        $this->assertSame(255, mb_strlen($item->title));
        $this->assertSame('Umum', $item->category);
        $this->assertSame('', $item->excerpt);
        $this->assertSame(['title' => $item->title, 'category' => 'Umum', 'excerpt' => '', 'content' => 'Isi'], $item->toArray());
    }
}
