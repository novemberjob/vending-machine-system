<?php $title = 'Register'; ?>
<section class="card narrow">
    <h1>Create an account</h1>
    <form method="post" action="/register" data-validate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <label for="name">Name</label>
        <input id="name" name="name" type="text" required maxlength="100" value="<?= e(old($old, 'name')) ?>">
        <?= field_error($errors, 'name') ?>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="255" value="<?= e(old($old, 'email')) ?>">
        <?= field_error($errors, 'email') ?>

        <label for="password">Password (min. 8 characters)</label>
        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
        <?= field_error($errors, 'password') ?>

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required
               minlength="8" autocomplete="new-password">
        <?= field_error($errors, 'password_confirmation') ?>

        <button class="button primary" type="submit">Register</button>
    </form>
    <p>Already registered? <a href="/login">Log in</a>.</p>
</section>
