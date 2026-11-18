<?php $title = 'New product'; ?>
<section class="card narrow">
    <div class="card-header"><h1>New product</h1></div>
    <form method="post" action="/products" data-validate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <label for="name">Name</label>
        <input id="name" name="name" type="text" required maxlength="100"
               value="<?= e(old($old, 'name')) ?>">
        <?= field_error($errors, 'name') ?>

        <label for="price">Price (USD)</label>
        <input id="price" name="price" type="number" required min="0.001" step="0.001"
               placeholder="0.000" value="<?= e(old($old, 'price')) ?>">
        <?= field_error($errors, 'price') ?>

        <label for="quantity_available">Quantity available</label>
        <input id="quantity_available" name="quantity_available" type="number" required min="0" step="1"
               value="<?= e(old($old, 'quantity_available', 0)) ?>">
        <?= field_error($errors, 'quantity_available') ?>

        <div class="form-actions">
            <button class="button primary" type="submit">Create product</button>
            <a class="button ghost" href="/products">Cancel</a>
        </div>
    </form>
</section>
