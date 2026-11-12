# Perbaikan Trivy (Jenkins)

Berkas di zip ini (timpa di AKAR repo Git, folder yang berisi `Jenkinsfile`):

- `package.json`       : paket build front-end dipindah ke `devDependencies`; `@laravel/multiplex` (alat terminal opsional) dilepas
- `package-lock.json`  : disesuaikan; versi paket yang tersisa TIDAK berubah
- `.gitignore`         : menambahkan folder salinan bersarang agar tidak ikut ke Git

Langkah lengkap ada di chat.
