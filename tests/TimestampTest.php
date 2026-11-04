<?php

declare(strict_types=1);

namespace Maximaster\Kilbil\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Maximaster\Kilbil\ValueObject\Timestamp;
use PHPUnit\Framework\TestCase;
use Poagen\Runtime\BindingContext;

/**
 * Регресс на асимметрию форматов даты Kilbil.
 *
 * Тестовый сервер ПРИНИМАЕТ дефис (`Y-m-d H:i:s`) на входе
 * (processsale/confirmsale — проверено живьём 2026-08-06), но
 * ВОЗВРАЩАЕТ точки (`Y.m.d H:i:s`) на выходе:
 * getdetailingbymove.move_info.move_date пришёл как `2026.08.06 18:28:24`.
 * Родной парсер DateTimeImmutable на такой строке падает ("Double time
 * specification"), поэтому fromScalar обязан пробовать оба разделителя.
 */
final class TimestampTest extends TestCase
{
    public function testParsesHyphenSeparatedInput(): void
    {
        $timestamp = Timestamp::fromScalar('2026-08-06 18:28:24');

        self::assertSame('2026-08-06 18:28:24', $timestamp->value->format('Y-m-d H:i:s'));
    }

    public function testParsesDotSeparatedInputAsKilbilReturnsIt(): void
    {
        $timestamp = Timestamp::fromScalar('2026.08.06 18:28:24');

        self::assertSame('2026-08-06 18:28:24', $timestamp->value->format('Y-m-d H:i:s'));
    }

    public function testSerializesWithHyphensWhichTheServerAccepts(): void
    {
        $timestamp = Timestamp::fromScalar('2026.08.06 18:28:24');

        self::assertSame('2026-08-06 18:28:24', $timestamp->toScalar());
    }

    public function testThrowsOnAnUnparseableValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Timestamp::fromScalar('not a date');
    }

    /**
     * Kilbil отдаёт время БЕЗ пояса, и это московское время. Разобранное
     * значение обязано быть настоящим моментом со смещением +03:00, а не
     * голыми цифрами в поясе процесса.
     */
    public function testParsesAValueWithoutAnOffsetAsMoscowTime(): void
    {
        $timestamp = Timestamp::fromScalar('2026-08-14 12:51:17');

        self::assertSame('2026-08-14T12:51:17+03:00', $timestamp->value->format('c'));
    }

    public function testParsesDotSeparatedInputAsMoscowTimeToo(): void
    {
        $timestamp = Timestamp::fromScalar('2026.08.14 12:51:17');

        self::assertSame('2026-08-14T12:51:17+03:00', $timestamp->value->format('c'));
    }

    /**
     * Ядро дефекта: в контейнере пояс процесса — UTC, а на машине
     * разработчика может быть любой. Один и тот же ответ вендора обязан
     * означать один и тот же момент в обоих случаях.
     */
    public function testTheProcessTimezoneDoesNotDecideWhatTheValueMeans(): void
    {
        $original = date_default_timezone_get();

        try {
            date_default_timezone_set('America/New_York');
            $timestamp = Timestamp::fromScalar('2026-08-14 12:51:17');

            self::assertSame('2026-08-14T12:51:17+03:00', $timestamp->value->format('c'));
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * Обратное направление: `processsale`/`confirmsale` получают московские
     * цифры независимо от того, в каком поясе вызывающий построил момент.
     * До этой правки правильные цифры держались на том, что четыре разных
     * класса помнили про `Europe/Moscow` каждый сам за себя.
     */
    public function testSerializesMoscowWallClockWhateverZoneTheCallerBuilt(): void
    {
        $utc = new Timestamp(new DateTimeImmutable('2026-08-14T09:51:17+00:00'));

        self::assertSame('2026-08-14 12:51:17', $utc->toScalar());
    }

    public function testRoundTripIsStable(): void
    {
        self::assertSame('2026-08-14 12:51:17', Timestamp::fromScalar('2026-08-14 12:51:17')->toScalar());
    }

    /**
     * Московский пояс — ДЕФОЛТ пакета, а не единственный ответ. Потребитель,
     * чья установка Kilbil работает в другом поясе, говорит об этом один раз в
     * `BindingContext`, и это выигрывает — без правки пакета.
     */
    public function testAContextFromTheConsumerBeatsThePackagesDefaultZone(): void
    {
        $timestamp = Timestamp::fromScalarIn(
            '2026-08-14 12:51:17',
            new BindingContext(new DateTimeZone('Asia/Tokyo')),
        );

        self::assertSame('2026-08-14T12:51:17+09:00', $timestamp->value->format('c'));
    }

    public function testTheSameContextDecidesWhatGoesBackOnTheWire(): void
    {
        $tokyo = new BindingContext(new DateTimeZone('Asia/Tokyo'));
        $timestamp = new Timestamp(new DateTimeImmutable('2026-08-14T09:51:17+00:00'));

        self::assertSame('2026-08-14 18:51:17', $timestamp->toScalarIn($tokyo));
        // А без контекста — по-прежнему московские цифры.
        self::assertSame('2026-08-14 12:51:17', $timestamp->toScalar());
    }

    /**
     * Пояс подставляется ТОЛЬКО там, где вендор его не прислал: строка со
     * своим смещением сохраняет его (проверено на PHP 8.4.17). Поэтому
     * настройка чинит только неполноценные значения и никогда не переписывает
     * корректное.
     */
    public function testAnExplicitOffsetInThePayloadWins(): void
    {
        $timestamp = Timestamp::fromScalar('2026-08-14T12:51:17+05:00');

        self::assertSame('2026-08-14T12:51:17+05:00', $timestamp->value->format('c'));
    }
}
