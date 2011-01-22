<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\FileBag;

/**
 * FileBagTest.
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class FileBagTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldIgnoreNonArrayValues()
    {
        $bag = new FileBag();

        $bag->set('file', '/img/image.jpg');

        $this->assertFalse($bag->has('file'));
    }

    public function testShouldSetNotUploadedFileToNull()
    {
        $bag = new FileBag();

        $bag->set('file', array(
            'error'    => UPLOAD_ERR_NO_FILE,
            'name'     => '',
            'size'     => '',
            'tmp_name' => '',
            'type'     => ''
        ));

        $this->assertNull($bag->get('file'));
    }

    public function testShouldFixPhpFilesArray()
    {
        $bag = new FileBag();

        $name = 'FileBagTest.php';
        $size = filesize(__FILE__);
        $type = 'text/x-php';

        $bag->set('file', array(
            'error'    => array('image' => UPLOAD_ERR_OK),
            'name'     => array('image' => $name),
            'size'     => array('image' => $size),
            'tmp_name' => array('image' => __FILE__),
            'type'     => array('image' => $type),
        ));

        $file = $bag->get('file');
        $image = $file['image'];

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\File\UploadedFile', $image);
        $this->assertEquals($type, $image->getMimeType());
        $this->assertEquals($name, $image->getOriginalName());
        $this->assertEquals($size, $image->size());
        $this->assertEquals($name, $image->getName());
    }
}
