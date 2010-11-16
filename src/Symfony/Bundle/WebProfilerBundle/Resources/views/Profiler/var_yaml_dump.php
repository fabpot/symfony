<?php if (is_object($value) && get_parent_class($value) == 'Symfony\Component\OutputEscaper\BaseEscaper'): ?>
    <?php $value = $value->getRawValue() ?>
<?php endif; ?>

<tr>
    <th><?php echo $key ?></th>
    <td>
        <?php if (is_resource($value)): ?>
            <em>Resource</em>
        <?php elseif (is_array($value) || is_object($value)): ?>
            <em><?php echo ucfirst(gettype($value)); ?></em>
            <pre><?php echo Symfony\Component\Yaml\Inline::dump($value); ?></pre>
        <?php else: ?>
            <?php echo $value ?>
        <?php endif; ?>
    </td>
</tr>
