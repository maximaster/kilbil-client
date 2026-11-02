<?php

declare(strict_types=1);

use Maximaster\Kilbil\ValueObject\CalendarDate;
use Maximaster\Kilbil\ValueObject\Money;
use Maximaster\Kilbil\ValueObject\PhoneNumber;
use Maximaster\Kilbil\ValueObject\Timestamp;
use Poagen\Generator\Generator\Profile;

/**
 * Guidance for the kilbil loyalty API.
 *
 * Regenerating the committed tree needs the generator; the client itself only
 * needs `poagen/client-runtime`. Delete everything below `namespace:` and the
 * run still succeeds, the client is just less pleasant to call.
 */
return new Profile(
    schema: __DIR__ . '/schemas/loyalty_v1.yaml',
    output: __DIR__ . '/generated',
    namespace: 'Maximaster\\Kilbil',

    /*
     * Nine format values, 292 uses, and only `date` matches a built-in. The
     * vendor writes `date time` and `data time` for `date-time`, `data` for
     * `date`, and invents `phone`. These four bindings answer all of them;
     * `int` and `double` stay unbound because each covers unrelated meanings
     * document-wide.
     */
    formats: [
        'date time' => Timestamp::class,
        'data time' => Timestamp::class,
        'data' => CalendarDate::class,
        'phone' => PhoneNumber::class,
    ],

    /*
     * `goods_data/items/properties/price` is money, and the vendor says so in
     * its own description. `double` also covers quantities, rates and
     * percentages, so this binds per property instead of document-wide.
     */
    propertyFormats: [
        'SearchClientRequestContentGoodsDataItem.price' => Money::class,
        'ProcessSaleRequestContentGoodsDataItem.price' => Money::class,
    ],

    /*
     * `goodsxml` and `goodslistxml` declare `application/xml` bodies with no
     * `xml:` metadata. The vendor's own `example:` blocks name the root and
     * item elements, and both nest one level through an `inside` wrapper.
     */
    xmlElements: [
        'goodsxml' => 'goods',
        'goodsxml.item' => 'good',
        'goodsxml.item.inside' => 'good',
        'goodslistxml' => 'goodslist',
        'goodslistxml.item' => 'goodlist',
        'goodslistxml.item.inside' => 'goodlist',
    ],

    /*
     * All eleven tags are Russian words. `Wallet` is already ASCII and is
     * deliberately absent.
     */
    tagRenames: [
        'Другое' => 'Misc',
        'Информация о лояльности' => 'LoyaltySettings',
        'Клиенты' => 'Customers',
        'Код подтверждения' => 'ConfirmationCodes',
        'Номенклатура' => 'Catalogue',
        'Операции' => 'Transactions',
        'План-факт' => 'SalesPlan',
        'Подарочные сертификаты' => 'GiftCertificates',
        'Промокоды' => 'Promocodes',
        'Теги' => 'Tags',
    ],

    /*
     * Operation ids are lowercase and run together, so the readable name is
     * consumer guidance. `generate_promocode`, `legal_info` and `goods` are
     * deliberately absent: the first two already split on their separators,
     * and the third is one word.
     */
    methodRenames: [
        'addclient' => 'addClient',
        'addclienttags' => 'addClientTags',
        'askconfirmphone' => 'askConfirmPhone',
        'blockcard' => 'blockCard',
        'checkconfirmphonecode' => 'checkConfirmPhoneCode',
        'closesession' => 'closeSession',
        'confirmsale' => 'confirmSale',
        'deleteclienttags' => 'deleteClientTags',
        'exchangepossettings' => 'exchangePosSettings',
        'generatecard' => 'generateCard',
        'generategiftcard' => 'generateGiftCard',
        'getactionslist' => 'getActionsList',
        'getclientcards' => 'getClientCards',
        'getclientfullbalance' => 'getClientFullBalance',
        'getclienttags' => 'getClientTags',
        'getconfirmationcode' => 'getConfirmationCode',
        'getdetailingbymove' => 'getDetailingByMove',
        'getlisttags' => 'getListTags',
        'getmovesbyclient' => 'getMovesByClient',
        'getprogramgiftcard' => 'getProgramGiftCard',
        'getprograms' => 'getPrograms',
        'getsalesindicesslides' => 'getSalesIndicesSlides',
        'getwalletlink' => 'getWalletLink',
        'goodslist' => 'goodsList',
        'goodslistxml' => 'goodsListXml',
        'goodsxml' => 'goodsXml',
        'manualadd' => 'manualAdd',
        'processsale' => 'processSale',
        'replacecard' => 'replaceCard',
        'reservedpromocode' => 'reservedPromocode',
        'searchclient' => 'searchClient',
        'searchgiftcard' => 'searchGiftCard',
        'searchpromocode' => 'searchPromocode',
    ],
);
