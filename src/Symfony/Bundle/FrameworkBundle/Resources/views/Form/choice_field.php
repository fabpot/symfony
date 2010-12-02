<?php if ($field->isExpanded()): ?>
    <?php foreach ($field as $choice => $child): ?>
        <?php echo $view['form']->render($child) ?>
        <label for="<?php echo $child->getId() ?>"><?php echo $field->getLabel($choice) ?></label>
    <?php endforeach ?>
<?php else: ?>
    <select
        id="<?php echo $field->getId() ?>"
        name="<?php echo $field->getName() ?>"
        <?php if ($field->isDisabled()): ?> disabled="disabled"<?php endif ?>
        <?php if ($field->isMultipleChoice()): ?> multiple="multiple"<?php endif ?>
    >
        <?php if (count($field->getPreferredChoices()) > 0): ?>
            <?php foreach ($field->getPreferredChoices() as $choice => $label): ?>
                <?php if ($label instanceof \Traversable || is_array($label)): ?>
                    <optgroup label="<?php echo $choice ?>">
                        <?php foreach ($label as $nestedChoice => $nestedLabel): ?>
                            <option value="<?php echo $nestedChoice ?>"<?php if ($field->isChoiceSelected($nestedChoice)): ?> selected="selected"<?php endif?>>
                                <?php echo $nestedLabel ?>
                            </option>
                        <?php endforeach ?>
                    </optgroup>
                <?php else: ?>
                    <option value="<?php echo $choice ?>"<?php if ($field->isChoiceSelected($choice)): ?> selected="selected"<?php endif?>>
                        <?php echo $label ?>
                    </option>
                <?php endif ?>
            <?php endforeach ?>
            <option disabled="disabled"><?php echo isset($separator) ? $separator : '-----------------' ?></option>
        <?php endif ?>
        <?php foreach ($field->getOtherChoices() as $choice => $label): ?>
            <?php if ($label instanceof \Traversable || is_array($label)): ?>
                <optgroup label="<?php echo $choice ?>">
                    <?php foreach ($label as $nestedChoice => $nestedLabel): ?>
                        <option value="<?php echo $nestedChoice ?>"<?php if ($field->isChoiceSelected($nestedChoice)): ?> selected="selected"<?php endif?>>
                            <?php echo $nestedLabel ?>
                        </option>
                    <?php endforeach ?>
                </optgroup>
            <?php else: ?>
                <option value="<?php echo $choice ?>"<?php if ($field->isChoiceSelected($choice)): ?> selected="selected"<?php endif?>>
                    <?php echo $label ?>
                </option>
            <?php endif ?>
        <?php endforeach ?>
    </select>
<?php endif ?>