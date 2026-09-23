<?php

test('locks the storefront to one shop', function (): void {
    config()->set('shop.single_shop', false);

    expect(isSingleShop())->toBeTrue();
});
