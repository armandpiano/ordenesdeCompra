<?php
use Prosa\Orders\Application\Dto\OrderPreviewDto;
use Prosa\Orders\Application\Dto\OrderPreviewLineDto;
use Prosa\Orders\Application\Dto\StorePreviewDto;
/** @var OrderPreviewDto $preview */
/** @var string|null $message */
?>
<?php if (!isset($preview)): ?>
    <div class="alert alert-warning">No hay información de vista previa.</div>
    <p><a href="/index.php">Volver al inicio</a></p>
<?php return; endif; ?>

<div class="alert alert-info">
    <strong>Cliente:</strong> <?php echo htmlspecialchars($preview->client()->id()->padded(), ENT_QUOTES, 'UTF-8'); ?>
    | <?php echo htmlspecialchars($preview->client()->name(), ENT_QUOTES, 'UTF-8'); ?>
    | Lista de precios: <?php echo htmlspecialchars((string) $preview->client()->priceList(), ENT_QUOTES, 'UTF-8'); ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-warning"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php $pending = $preview->storesPending(); ?>
<?php if (empty($pending)): ?>
    <div class="alert alert-info">Todas las tiendas han sido procesadas.</div>
    <p><a href="/index.php">Volver al inicio</a></p>
<?php return; endif; ?>

<div class="store-selector">
    <form method="POST" action="?action=select-store">
        <label for="store">Selecciona tienda:</label>
        <select name="store" id="store">
            <?php foreach ($pending as $storeKey): ?>
                <option value="<?php echo htmlspecialchars($storeKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $preview->selectedStore() === $storeKey ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($storeKey, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Ver tienda</button>
    </form>
</div>

<?php $selected = $preview->selectedStore(); ?>
<?php if ($selected === null || $selected === ''): ?>
    <div class="alert alert-info">Selecciona una tienda para ver su detalle.</div>
    <?php return; endif; ?>

<?php /** @var StorePreviewDto $storeDto */ $storeDto = $preview->stores()[$selected]; ?>

<table>
    <thead>
    <tr>
        <th>Clave artículo</th>
        <th>Descripción SAE</th>
        <th>Cantidad</th>
        <th>Precio cliente</th>
        <th>Precio SAE</th>
        <th>Importe cliente</th>
        <th>Importe SAE</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($storeDto->lines() as $line): ?>
        <?php /** @var OrderPreviewLineDto $line */ ?>
        <tr>
            <td>
                <form method="POST" action="?action=update-line">
                    <input type="hidden" name="store" value="<?php echo htmlspecialchars($selected, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="line_index" value="<?php echo htmlspecialchars((string) $line->index(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" name="new_product_code" value="<?php echo htmlspecialchars((string) $line->productCode(), ENT_QUOTES, 'UTF-8'); ?>" size="8">
            </td>
            <td><?php echo htmlspecialchars($line->description(), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format($line->quantity(), 2); ?></td>
            <td><?php echo number_format($line->priceCliente(), 2); ?></td>
            <td><?php echo number_format($line->priceSae(), 2); ?></td>
            <td><?php echo number_format($line->importeCliente(), 2); ?></td>
            <td><?php echo number_format($line->importeSae(), 2); ?></td>
            <td>
                <?php if ($line->productCode() === null): ?>
                    No encontrado en catálogo
                <?php elseif (!$line->productFound()): ?>
                    No encontrado en SAE
                <?php else: ?>
                    OK (<?php echo htmlspecialchars($line->matchType(), ENT_QUOTES, 'UTF-8'); ?>)
                <?php endif; ?>
            </td>
            <td>
                    <input type="submit" value="Actualizar">
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="alert alert-info">
    Total cliente tienda: <?php echo number_format($storeDto->totalClient(), 2); ?> |
    Total SAE tienda: <?php echo number_format($storeDto->totalSae(), 2); ?>
</div>

<form method="POST" action="?action=generate-mod">
    <input type="hidden" name="store" value="<?php echo htmlspecialchars($selected, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit">Generar .MOD de esta tienda</button>
</form>

<p><a href="/index.php">Volver al inicio</a></p>
