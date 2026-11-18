<?php $title = 'Login'; ?>
<section class="card narrow">
    <h1>Login</h1>
    <?php if (!empty($errors['credentials'])): ?>
        <div class="alert error" role="alert"><?= e($errors['credentials']) ?></div>
    <?php endif; ?>
    <form method="post" action="/login" data-validate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required autocomplete="email"
               value="<?= e(old($old, 'email')) ?>">
        <?= field_error($errors, 'email') ?>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <?= field_error($errors, 'password') ?>

        <button class="button primary" type="submit">Log in</button>
    </form>
    <p>No account yet? <a href="/register">Register here</a>.</p>
</section>
