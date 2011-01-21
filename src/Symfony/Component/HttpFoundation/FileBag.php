<?php

namespace Symfony\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileBag extends ParameterBag
{
    public function __construct(array $files = array())
    {
        parent::__construct($this->convertFileInformation($files));
    }

    /**
     * Converts uploaded files to UploadedFile instances.
     *
     * @param  array $files A (multi-dimensional) array of uploaded file information
     *
     * @return array A (multi-dimensional) array of UploadedFile instances
     */
    protected function convertFileInformation(array $files)
    {
        $fixedFiles = array();

        foreach ($files as $key => $data) {
            $fixedFiles[$key] = $this->fixPhpFilesArray($data);
        }

        $fileKeys = array('error', 'name', 'size', 'tmp_name', 'type');
        foreach ($fixedFiles as $key => $data) {
            if (is_array($data)) {
                $keys = array_keys($data);
                sort($keys);

                if ($keys == $fileKeys) {
                    $data['error'] = (int) $data['error'];
                }

                if ($keys != $fileKeys) {
                    $fixedFiles[$key] = $this->convertFileInformation($data);
                } else if ($data['error'] === UPLOAD_ERR_NO_FILE) {
                    $fixedFiles[$key] = null;
                } else {
                    $fixedFiles[$key] = new UploadedFile($data['tmp_name'], $data['name'], $data['type'], $data['size'], $data['error']);
                }
            }
        }

        return $fixedFiles;
    }

    /**
     * Fixes a malformed PHP $_FILES array.
     *
     * PHP has a bug that the format of the $_FILES array differs, depending on
     * whether the uploaded file fields had normal field names or array-like
     * field names ("normal" vs. "parent[child]").
     *
     * This method fixes the array to look like the "normal" $_FILES array.
     *
     * It's safe to pass an already converted array, in which case this method
     * just returns the original array unmodified.
     *
     * @param  array $data
     * @return array
     */
    protected function fixPhpFilesArray($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $fileKeys = array('error', 'name', 'size', 'tmp_name', 'type');
        $keys = array_keys($data);
        sort($keys);

        if ($fileKeys != $keys || !isset($data['name']) || !is_array($data['name'])) {
            return $data;
        }

        $files = $data;
        foreach ($fileKeys as $k) {
            unset($files[$k]);
        }
        foreach (array_keys($data['name']) as $key) {
            $files[$key] = $this->fixPhpFilesArray(array(
                'error'    => $data['error'][$key],
                'name'     => $data['name'][$key],
                'type'     => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'size'     => $data['size'][$key],
            ));
        }

        return $files;
    }
}
