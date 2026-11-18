<?php $title = 'Buy ' . $product->name; ?>
<section class="card narrow">
    <div class="card-header"><h1>Purchase <?= e($product->name) ?></h1></div>

    <dl class="details">
        <dt>Product</dt><dd><?= e($product->name) ?> (#<?= e($product->id) ?>)</dd>
        <dt>Unit price</dt><dd class="price" id="unit-price" data-price="<?= e($product->price) ?>"><?= e($product->formattedPrice()) ?> USD</dd>
        <dt>Available</dt><dd><?= e($product->quantityAvailable) ?> unit(s)</dd>
    </dl>

    <?php if ($product->quantityAvailable === 0): ?>
        <div class="alert error" role="alert">This product is out of stock.</div>
        <a class="button ghost" href="/products">Back to products</a>
    <?php else: ?>
        <form method="post" action="/products/<?= e($product->id) ?>-<?= e($product->slug()) ?>/buy" data-validate>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <label for="quantity">Quantity (1 - <?= e($product->quantityAvailable) ?>)</label>
            <input id="quantity" name="quantity" type="number" required min="1" step="1"
                   max="<?= e($product->quantityAvailable) ?>" value="<?= e(old($old, 'quantity', 1)) ?>">
            <?= field_error($errors, 'quantity') ?>

            <p class="total">Estimated total: <strong id="total"><?= e(number_format((float) $product->price, 3, '.', ',')) ?> USD</strong></p>

            <div class="form-actions">
                <button class="button primary" type="submit">Confirm purchase</button>
                <a class="button ghost" href="/products">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</section>
