<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Mapping\Loader;

/**
 * Loads validation metadata from a list of XML files.
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see FilesLoader
 *
 * @deprecated since Symfony 7.3
 */
class XmlFilesLoader extends FilesLoader
{
    public function __construct(array $paths)
    {
        trigger_deprecation('symfony/validator', '7.3', \sprintf('The "%s" class is deprecated.', __CLASS__));

        parent::__construct($paths);
    }

    public function getFileLoaderInstance(string $file): LoaderInterface
    {
        return new XmlFileLoader($file, false);
    }
}
