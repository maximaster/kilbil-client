# Разработка

```bash
composer install
composer test
composer lint
```

`generated/` пишет poagen, руками его не правят. Перегенерация требует
отдельного чекаута poagen:

```bash
php ../poagen/bin/poagen generate poagen.php
git diff --exit-code
```

Схема вендора лежит в `schemas/loyalty_v1.yaml`. Записанные ответы и
фикстуры — в `fixtures/responses/`.
