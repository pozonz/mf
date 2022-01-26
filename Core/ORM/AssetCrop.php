<?php
//Last updated: 2019-05-09 21:08:18
namespace MillenniumFalcon\Core\ORM;

use MillenniumFalcon\Core\ORM\Traits\AssetCropTrait;
use Symfony\Component\Validator\Constraints as Assert;

class AssetCrop extends \MillenniumFalcon\Core\ORM\Generated\AssetCrop
{
    use AssetCropTrait;

    /** @Assert\Positive() */
    public function getX(): int
    {
        return (int) parent::getX();
    }

    public function setX($x): void
    {
        parent::setX((int)$x);
    }

    /** @Assert\Positive() */
    public function getY(): int
    {
        return (int) parent::getY();
    }

    public function setY($y): void
    {
        parent::setY((int)$y);
    }

    /** @Assert\Positive() */
    public function getWidth(): int
    {
        return (int) parent::getWidth();
    }

    public function setWidth($width): void
    {
        parent::setWidth((int)$width);
    }

    /** @Assert\Positive() */
    public function getHeight(): int
    {
        return (int) parent::getHeight();
    }

    public function setHeight($height): void
    {
        parent::setHeight((int)$height);
    }

}
