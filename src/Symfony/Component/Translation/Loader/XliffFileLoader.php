<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\Translation\Loader;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Resource\FileResource;

/**
 * XliffFileLoader loads translations from XLIFF files.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class XliffFileLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $xml = $this->parseFile($resource);
        $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');

        $catalogue = new MessageCatalogue($locale);
        foreach ($xml->xpath('//xliff:trans-unit') as $translation) {
            $catalogue->set((string) $translation->source, (string) $translation->target, $domain);
        }
        $catalogue->addResource(new FileResource($resource));

        return $catalogue;
    }

    /**
     * Validates and parses the given file into a SimpleXMLElement
     *
     * @param  string $file
     * @return SimpleXMLElement
     */
    protected function parseFile($file)
    {
        $dom = new \DOMDocument();
        $current = libxml_use_internal_errors(true);
        if (!@$dom->load($file, LIBXML_COMPACT)) {
            throw new \Exception(implode("\n", $this->getXmlErrors()));
        }

        $parts = explode('/', str_replace('\\', '/', __DIR__).'/schema/dic/xliff-core/xml.xsd');
        $drive = '\\' === DIRECTORY_SEPARATOR ? array_shift($parts).'/' : '';
        $location = 'file:///'.$drive.implode('/', array_map('rawurlencode', $parts));

        $source = file_get_contents(__DIR__.'/schema/dic/xliff-core/xliff-core-1.2-strict.xsd');
        $source = str_replace('http://www.w3.org/2001/xml.xsd', $location, $source);

        if (!@$dom->schemaValidateSource($source)) {
            throw new \Exception(implode("\n", $this->getXmlErrors()));
        }
        $dom->validateOnParse = true;
        $dom->normalizeDocument();
        libxml_use_internal_errors($current);

        return simplexml_import_dom($dom);
    }

    /**
     * Returns the XML errors of the internal XML parser
     *
     * @return array  An array of errors
     */
    protected function getXmlErrors()
    {
        $errors = array();
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf('[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ? $error->file : 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return $errors;
    }
}
