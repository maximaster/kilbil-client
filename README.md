# kilbil-client

Клиент API программы лояльности Kilbil, сгенерированный из
[документа вендора](https://docs.kilbil.ru/openapi/loyalty_v1.yaml).

```bash
composer require maximaster/kilbil-client
```

```php
use Maximaster\Kilbil\Generated\ApiClient;
use Maximaster\Kilbil\Generated\ApiException;
use Poagen\Runtime\Http\HttpTransport;

$client = new ApiClient(new HttpTransport(ApiClient::SERVER_1, ApiException::class), h: getenv('KILBIL_API_KEY'));
```

Источник: `loyalty_v1.yaml` версии 1.5.3, получен 29.07.2026; часть схемы
сверена с тестовым API 05.08.2026. Вендор объявил в документе лицензию
`unlicensed`; пакет публикуется с этим известным фактом.
