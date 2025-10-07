<?php

declare(strict_types=1);

use Mobicms\System\Config\ConfigContainer;
use Mobicms\System\Config\Exception\KeyAlreadyExistsException;

describe('has()', function () {
    $config = new ConfigContainer(['foo' => 'bar']);

    test('requesting an existing key returns true', function () use ($config) {
        expect($config->has('foo'))->toBeTrue();
    });

    test('request for a nonexistent key returns false', function () use ($config) {
        expect($config->has('baz'))->toBeFalse();
    });
});

describe('get()', function () {
    $config = new ConfigContainer(
        [
            'int'    => 12345,
            'string' => 'teststring',
            'array'  =>
                [
                    'foo'    => 'bar',
                    'nested' => ['baz' => 'bat'],
                ],
            'bool'   => true,
        ]
    );

    test('can return flat data', function () use ($config) {
        expect($config->get('int'))->toEqual(12345)
            ->and($config->get('string'))->toEqual('teststring')
            ->and($config->get('array'))->toBeArray()
            ->and($config->get('bool'))->toBeTrue();
    });

    test('can return nested array data', function () use ($config) {
        expect($config->get(['array', 'foo']))->toEqual('bar')
            ->and($config->get(['array', 'nested', 'baz']))->toEqual('bat');
    });

    test('can return default value', function () use ($config) {
        expect($config->get('foo'))->toBeNull()
            ->and($config->get('foo', 'string'))->toEqual('string')
            ->and($config->get('foo', 12345))->toEqual(12345)
            ->and($config->get('foo', true))->toBeTrue()
            ->and($config->get(['foo', 'bar'], 'string'))->toEqual('string');
    });
});

test('Set method can store data', function () {
    $config = new ConfigContainer();
    $config->set('string', 'test');
    $config->set('int', 12345);
    $config->set(
        'array',
        [
            'foo' => 'bar',
            'baz' => 'bat',
        ]
    );

    expect($config->get('string'))->toEqual('test')
        ->and($config->get('int'))->toEqual(12345)
        ->and($config->get('array'))->toEqual(['foo' => 'bar', 'baz' => 'bat']);
});

test('Unset method', function () {
    $config = new ConfigContainer(['foo' => 'bar']);
    expect($config->has('foo'))->toBeTrue();
    $config->unset('foo');
    expect($config->has('foo'))->toBeFalse();
});

describe('Exception handling:', function () {
    test('on existing key', function () {
        $config = new ConfigContainer(['foo' => 'bar']);
        $config->set('foo', 'somedata');
    })->throws(KeyAlreadyExistsException::class);
});
