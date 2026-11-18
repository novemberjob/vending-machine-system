<?php $title = $product->name; ?>
<section class="card narrow">
    <div class="card-header"><h1><?= e($product->name) ?></h1></div>
    <dl class="details">
        <dt>Product ID</dt><dd>#<?= e($product->id) ?></dd>
        <dt>Price</dt><dd class="price"><?= e($product->formattedPrice()) ?> USD</dd>
        <dt>Available</dt><dd><?= e($product->quantityAvailable) ?> unit(s)</dd>
        <dt>Created</dt><dd><?= e($product->createdAt ?? '-') ?></dd>
    </dl>
    <div class="form-actions">
        <?php if (can_purchase($user) && $product->quantityAvailable > 0): ?>
            <a class="button primary" href="/products/<?= e($product->id) ?>-<?= e($product->slug()) ?>/buy">Buy now</a>
        <?php endif; ?>
        <a class="button ghost" href="/products">Back to products</a>
    </div>
</section>
