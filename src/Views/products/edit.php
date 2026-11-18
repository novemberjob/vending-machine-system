<?php $title = 'Edit product'; ?>
<section class="card narrow">
    <div class="card-header"><h1>Edit product #<?= e($product?->id ?? '') ?></h1></div>
    <form method="post" action="/products/<?= e($product?->id ?? 0) ?>" data-validate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <label for="name">Name</label>
        <input id="name" name="name" type="text" required maxlength="100"
               value="<?= e(old($old, 'name', $product?->name ?? '')) ?>">
        <?= field_error($errors, 'name') ?>

        <label for="price">Price (USD)</label>
        <input id="price" name="price" type="number" required min="0.001" step="0.001"
               value="<?= e(old($old, 'price', $product?->price ?? '')) ?>">
        <?= field_error($errors, 'price') ?>

        <label for="quantity_available">Quantity available</label>
        <input id="quantity_available" name="quantity_available" type="number" required min="0" step="1"
               value="<?= e(old($old, 'quantity_available', $product?->quantityAvailable ?? 0)) ?>">
        <?= field_error($errors, 'quantity_available') ?>

        <div class="form-actions">
            <button class="button primary" type="submit">Save changes</button>
            <a class="button ghost" href="/products">Cancel</a>
        </div>
    </form>
</section>
