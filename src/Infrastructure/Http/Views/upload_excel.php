<?php if (isset($error)): ?>
    <div class="alert alert-warning"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<form method="POST" action="?action=import-excel" enctype="multipart/form-data">
    <div style="margin-top: 0.5rem;">
        <label for="order_excel">Archivo Excel</label><br>
        <input type="file" name="order_excel" id="order_excel" accept=".xls,.xlsx,.xlsm,.ods,.csv" required>
    </div>
    <div style="margin-top: 0.5rem;">
        <input type="submit" value="Importar Excel">
    </div>
</form>
<p><a href="/index.php">Volver</a></p>
