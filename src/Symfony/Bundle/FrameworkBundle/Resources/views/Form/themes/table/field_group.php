<?php echo $view['form']->errors($field) ?>

<table>
    <?php foreach ($field->getVisibleFields() as $child): ?>
        <tr>
            <th>
                <?php echo $view['form']->label($child) ?>
            </th>
            <td>
                <?php echo $view['form']->errors($child) ?>
                <?php echo $view['form']->render($child) ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php echo $view['form']->hidden($field) ?>
