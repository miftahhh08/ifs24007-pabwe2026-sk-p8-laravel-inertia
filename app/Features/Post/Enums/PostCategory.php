<?php

namespace App\Features\Post\Enums;

use Illuminate\Support\Str;

enum PostCategory: string
{
    case Teknologi = 'Teknologi';
    case Pendidikan = 'Pendidikan';
    case GayaHidup = 'Gaya Hidup';
    case Bisnis = 'Bisnis';
    case Opini = 'Opini';
    case Umum = 'Umum';

    /** Daftar nilai string, dipakai untuk validasi & dropdown. */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /** Cocokkan teks bebas (tidak peka huruf besar/kecil); jika tak cocok -> Umum. */
    public static function fromLabel(?string $label): self
    {
        $needle = Str::lower(trim((string) $label));

        foreach (self::cases() as $case) {
            if (Str::lower($case->value) === $needle) {
                return $case;
            }
        }

        return self::Umum;
    }

    public function description(): string
    {
        return match ($this) {
            self::Teknologi => 'Teknologi & Koding',
            self::Pendidikan => 'Pendidikan & Tutorial',
            self::GayaHidup => 'Gaya Hidup & Produktivitas',
            self::Bisnis => 'Bisnis & Karir',
            self::Opini => 'Opini & Pemikiran',
            self::Umum => 'Umum',
        };
    }
}
