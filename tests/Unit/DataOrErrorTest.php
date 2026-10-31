<?php

namespace Tests\Unit;

use App\Domain\Model\DataOrError;
use PHPUnit\Framework\TestCase;

class DataOrErrorTest extends TestCase
{
    public function test_valid_data_or_error_returns_true(): void
    {
        $result = new DataOrError(['key' => 'value'], null, false);

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->isError);
        $this->assertEquals(['key' => 'value'], $result->data);
        $this->assertNull($result->errors);
    }

    public function test_error_data_or_error_returns_false_for_is_valid(): void
    {
        $result = new DataOrError(null, ['Something went wrong'], true);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isError);
        $this->assertNull($result->data);
        $this->assertEquals(['Something went wrong'], $result->errors);
    }

    public function test_null_data_with_no_error_flag_is_not_valid(): void
    {
        $result = new DataOrError(null, null, false);

        $this->assertFalse($result->isValid());
    }

    public function test_data_with_errors_present_is_not_valid(): void
    {
        $result = new DataOrError(['data'], ['Some warning/error'], false);

        $this->assertFalse($result->isValid());
    }
}
