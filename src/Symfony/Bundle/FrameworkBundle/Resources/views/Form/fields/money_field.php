<?php echo str_replace('{{ widget }}',
    $view['form']->render($field, array(), array(), 'FrameworkBundle:Form:fields/number_field.php'),
    $field->getPattern()
) ?>
