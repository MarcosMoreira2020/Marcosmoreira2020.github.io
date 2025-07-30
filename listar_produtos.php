<?php
require_once 'config/db.php';   
?>


<table style="margin:30px auto; border-collapse:separate; border-spacing:0 12px; min-width:70%; background:#fff;">
    <thead>
        <tr style="background:#f0f0f0;">
            <th style="border:1px solid #ccc; padding:14px 18px;">ID</th>
            <th style="border:1px solid #ccc; padding:14px 18px;">Nome</th>
            <th style="border:1px solid #ccc; padding:14px 18px;">Preço</th>
            <th style="border:1px solid #ccc; padding:14px 18px;">Estoque</th>
            <th style="border:1px solid #ccc; padding:14px 18px;">Compra</th>
           
            <th style="border:1px solid #ccc; padding:14px 18px;">Imagem</th>
            <th style="border:1px solid #ccc; padding:14px 18px;">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $produtos = $conn->query("SELECT * FROM produtos ORDER BY nome ASC LIMIT 10 OFFSET 0");
        if ($produtos && $produtos->num_rows > 0):
            while ($p = $produtos->fetch_assoc()):
        ?>
        <tr style="box-shadow:0 2px 8px #eee;">
            <td style="border:1px solid #ccc; padding:14px 18px; background:#fafbfc;"><?= $p['produto_id'] ?></td>
            <td style="border:1px solid #ccc; padding:14px 18px;">
                <strong><?= htmlspecialchars($p['nome']) ?></strong>
            </td>


            <td style="border:1px solid #ccc; padding:14px 18px;"><?= number_format($p['preco'], 2, ',', '.') ?></td>
            <td style="border:1px solid #ccc; padding:14px 18px;"><?= $p['estoque'] ?></td>
            <td style="border:1px solid #ccc; padding:14px 18px;"><?= number_format($p['pre_compra'], 2, ',', '.') ?></td>
                      
                <?php if (!empty($p['imagem'])): ?>
                    <img src="imagens/<?= htmlspecialchars($p['imagem']) ?>" alt="Imagem" style="max-width:60px; max-height:60px; border-radius:6px; box-shadow:0 1px 4px #ccc;">
                <?php endif; ?>
            </td>
            <td style="border:1px solid #ccc; padding:14px 18px;">
                <a href="produtos.php?editar=<?= $p['produto_id'] ?>" style="color:#007bff;">Editar</a>
                <a href="produtos.php?excluir=<?= $p['produto_id']  ?> onclick="return confirm('Excluir este produto?');" style="color:#dc3545; margin-left:10px;">Excluir</a>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr>
            <td colspan="8" style="text-align:center; color:#888;">Nenhum produto cadastrado ainda.</td>
        </tr>
        <?php endif; ?>

        <a button="submit" name="pagina principal" style="margin-top: 20px; text-align: center;" href="index.php">Pagina Principal</a>
    </tbody>
</table>