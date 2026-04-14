<div class="page-header">
    <h1 class="page-title"><?= e(Lang::t('errors.csrf_title')) ?></h1>
</div>
<div class="card">
    <p><?= e(Lang::t('errors.csrf_text')) ?></p>
    <a href="javascript:history.back()" class="btn btn-outline"><?= e(Lang::t('errors.csrf_back')) ?></a>
    &nbsp;
    <a href="/" class="btn btn-ghost"><?= e(Lang::t('errors.back_home')) ?></a>
</div>
