<tr>
    <th><?php echo $key ?></th>
    <td>
        <?php if (is_resource($value)): ?>
            <em>Resource</em>
        <?php elseif (is_array($value) || is_object($value)): ?>
            <em><?php echo ucfirst(get_type($value)); ?></em>
            <pre><?php echo Symfony\Component\Yaml\Inline::dump($value); ?></pre>
        <?php else: ?>
            <?php echo $value ?>
        <?php endif; ?>
    </td>
</tr>
