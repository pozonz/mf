<?php

namespace MillenniumFalcon\Core\Asset;

use Doctrine\DBAL\Connection;
use GravitateNZ\fta\cms\assets\orm\ImageDescriptor;
use GravitateNZ\fta\cms\assets\orm\BinaryDataMetadata;
use GravitateNZ\fta\cms\assets\orm\BinaryData;

/**
 * Handles image manipulation
 * Class ImageService
 * @package GravitateNZ\fta\cms\assets
 */
class ImageService
{

    const OPTION_WIDTH = 'width';
    const OPTION_HEIGHT = 'height';
    const OPTION_PRESERVEASPECTRATIO = 'preserveaspectratio';
    const OPTION_UPSCALESMALLERIMAGES = 'upscalesmallerimages';
    const OPTION_ONLYRESIZEIFSMALLER = 'onlyresizeifsmaller';
    const OPTION_STRIPPROFILE = 'stripprofile';
    const OPTION_DEBUG = 'debug';
    const OPTION_VERBOSE = 'verbose';
    const OPTION_FILTER = 'filter';
    const OPTION_OUTPUT_FORMAT = 'outputformat';
    const OPTION_DEBUG_OPTIONS = 'debug.options';

    const CONVERT_ARG_RESIZE = '-resize';
    const CONVERT_ARG_STRIP_PROFILE = "+profile '*' -strip";
    const CONVERT_ARG_FILTER = '-filter';
    const CONVERT_ARG_VERBOSE = "-verbose";
    const OPTION_QUALITY = 'quality';
    const CONVERT_ARG_QUALITY = "-quality";
    const CONVERT_ARG_DEBUG = '-debug';

    protected $cachePath;
    protected $zdb;
    protected $options;

    protected $debug;
    protected $verbose = false;
    protected $debugOptions = 'All';

    protected $convertPath;
    protected $webpPath;
    protected $mozjpegPath;
    protected $optipngPath;

    protected $webpQualityFuzz = 5;
    protected $jpegQualityFuzz = 0;

    protected $pdfRasterService;

    /**
     * ImageService constructor.
     *
     * @param Connection $zdb
     * @param $cachePath
     */
    public function __construct(
        Connection $zdb,
        $cachePath,
        $convertPath,
        $mozjpegPath,
        $optipngPath,
        $webpPath,
        /** pdf raster service */
        PdfRasterService $pdfRasterService
    )
    {
        $this->zdb = $zdb;
        $this->cachePath = $cachePath;
        //currently we yank these from the config... not sure of a better way of doing this...
        $this->convertPath = $convertPath;
        $this->mozjpegPath = $mozjpegPath;
        $this->optipngPath = $optipngPath;
        $this->webpPath = $webpPath;
        $this->pdfRasterService = $pdfRasterService;
    }

    /**
     * get arguments to resize the image
     * @return string
     */
    protected function getResizeArguments(ImageDescriptor $imageDescriptor, $options = []): string
    {

        $command = '';

        $width = $imageDescriptor->data[static::OPTION_WIDTH] ?? null;
        $height = $imageDescriptor->data[static::OPTION_HEIGHT] ?? null;
        $preserveAspectRatio = ($imageDescriptor->data[static::OPTION_PRESERVEASPECTRATIO] ?? true) == true;

        //this allows images that are smaller than the specified size to be scaled up anyway..
        $upscaleSmallerImages = ($imageDescriptor->data[static::OPTION_UPSCALESMALLERIMAGES] ?? false) == true;
        $onlyResizeIfSmaller = ($imageDescriptor->data[static::OPTION_ONLYRESIZEIFSMALLER] ?? false) == true;

        if (!is_null($width) || !is_null($height)) {

            $resize = '';
            if (!is_null($width) && $width > 0) {
                $resize .= $width;
            }

            if (!is_null($height) && $height > 0) {
                $resize .= 'x' . $height;
            }

            //scale if bigger
            if (!$upscaleSmallerImages) {
                $resize .= "'>'";
            }

            //scale if smaller
            if ($onlyResizeIfSmaller) {
                $resize .= "'<'";
            }

            //force the scaled size
            if (!$preserveAspectRatio) {
                $resize .= '!';
            }

            $command .= static::CONVERT_ARG_RESIZE . ' ' . $resize;
        }

        return $command;
    }

    /**
     * get the arguments to trip exif data etc.
     * @return string
     */
    protected function getStripProfileArguments(ImageDescriptor $imageDescriptor): string
    {
        if (true == ($imageDescriptor->data[static::OPTION_STRIPPROFILE] ?? true)) {
            return static::CONVERT_ARG_STRIP_PROFILE;
        }
        return '';
    }

    /**
     * gets the arguments to set debug/verbose mode
     * @return string
     * @param $options
     */
    protected function getVerboseArguments(ImageDescriptor $imageDescriptor, $options = []): string
    {

        $command = [];

        if ($this->verbose) {
            $command[] = static::CONVERT_ARG_VERBOSE;
        }

        if ($this->debug) {
            $command[] = static::CONVERT_ARG_DEBUG . ' ' . $this->debugOptions;
        }

        return implode(' ', $command);
    }

    /**
     * returns the command line arguments for image filtering
     * @return string
     */
    protected function getFilterArguments(ImageDescriptor $imageDescriptor): string
    {
        $command = '';
        $filter = '' . ($imageDescriptor->data[static::OPTION_FILTER] ?? '');
        if (strlen($filter) > 0) {
            $command = static::CONVERT_ARG_FILTER . ' ' . $filter;
        }
        return $command;
    }


    /**
     * @return string
     */
    protected function getColourSpaceArguments(ImageDescriptor $imageDescriptor, $options = []): string
    {

        $outputFormat = $imageDescriptor->data[static::OPTION_OUTPUT_FORMAT] ?? 'JPEG';

        switch ($outputFormat) {
            case 'PNG':
                return '-colorspace sRGB';
                break;
            case 'WEBP':
                return '-colorspace sRGB';
                break;
            default:
                return '-colorspace sRGB -background white -alpha remove';
        }
    }

    /**
     * @return string
     */
    protected function getIntermediateOutputFormat(ImageDescriptor $imageDescriptor, $options = []): string
    {

        $outputFormat = $imageDescriptor->data[static::OPTION_OUTPUT_FORMAT] ?? 'JPEG';

        //preferred intermediate formats
        //map to a lossess format so we can convert to webp later.
        if ($outputFormat === 'WEBP') {
            return 'PNG32';
        }


        if ($outputFormat === 'JPEG') {
            return 'TGA';
        }

        return $outputFormat;
    }


    /**
     * @param array $options
     *
     * @return string
     */
    protected function getCropArguments(ImageDescriptor $imageDescriptor, $options = []): string
    {
        return '' . ($options['crop'] ?? '');
    }

    /**
     * @param array $options
     * @return string
     */
    public function getCommandLineArguments(ImageDescriptor $imageDescriptor, $options = []): string
    {

        //basically build a pipe line of commands...
        return implode(' ', [
            $this->getVerboseArguments($imageDescriptor, $options),
            $this->getStripProfileArguments($imageDescriptor, $options),

            //crops have to occur before scaling, crops don't originate in the image desc, but options
            $this->getCropArguments($imageDescriptor, $options),


            $this->getFilterArguments($imageDescriptor, $options),

            $this->getResizeArguments($imageDescriptor, $options),
            $this->getColourSpaceArguments($imageDescriptor, $options),
            $this->getIntermediateOutputFormat($imageDescriptor, $options)
        ]);
    }

    // todo: optimise image pipeline, possibly using temp files so we don't have to gobble lots of memory...

    /**
     *
     * @param ImageDescriptor $imageDescriptor
     * @param BinaryData $binaryData
     * @param array $options
     * @return string
     */
    public function &applyImageDescriptorToBinaryData(
        ImageDescriptor $imageDescriptor,
        BinaryDataMetadata $binaryDataMetadata,
        $options = []
    ): string
    {

        $binaryData = $binaryDataMetadata->getAsBinaryData();


        //todo: throw something here

        // if we get an svg, just return it, as resizing them makes no sense.
        if ($binaryData->data['mimetype'] == 'image/svg+xml') {
            return $binaryData->data['content'];
        }

        // if we have
        if ($imageDescriptor->filetype == 1) {
            return $binaryData->data['content'];
        }

        //hook here for rasterisation of pdf...
        if ($binaryData->data['mimetype'] == 'application/pdf') {
            // method to raster the pdf...
            // we could end up with a stack of these different raster services... for different formats?
            // $binaryData = // replace the binary data with a representation...
            $binaryData = $this->pdfRasterService->rasterPdfBinaryData($binaryData);
        }


        // command line to handle resize and intermediate formats...
        $options[static::OPTION_DEBUG] = $this->debug;
        $options[static::OPTION_DEBUG_OPTIONS] = $this->debugOptions;
        $options[static::OPTION_VERBOSE] = $this->verbose;

        $command = $this->convertPath . " - " . $this->getCommandLineArguments($imageDescriptor, $options) . ':-';

        $out = "";
        $returnValue = 0 != $this->generateOutput($command, $binaryData->data['content'], $out);
        if ($returnValue) {
            throw new \LogicException("convert returned: " . $returnValue);
        }

        $quality = $imageDescriptor->data['quality'] ?? 90;
        $quality = $quality > 1 ? $quality : $quality * 100;

        //how are we converting the image...
        switch ($imageDescriptor->outputformat) {
            case 'WEBP':
                $this->outputWEBP($quality, $out);
                break;

            case 'JPEG':
                $this->outputJPEG($quality, $out);
                break;

            case 'PNG':
            case 'PNG8':
            case 'PNG24':
            case 'PNG32':
                $this->outputPNG($out);
                break;
        }

        return $out;
    }


    /**
     * run the command and grab the in/out
     * @param $command
     * @param string $in
     * @param null $out
     *
     * @return int
     */
    protected function generateOutput($command, &$in = '', &$out = null)
    {

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", $this->cachePath . '/error-output.txt', 'a') // stderr is a file to write to
        );

        $returnValue = -999;

        $process = proc_open($command, $descriptorspec, $pipes);
        if (is_resource($process)) {

            fwrite($pipes[0], $in);
            fclose($pipes[0]);

            $out = "";
            //read the output
            while (!feof($pipes[1])) {
                $out .= fgets($pipes[1], 4096);
            }
            fclose($pipes[1]);
            $returnValue = proc_close($process);
        }

        return $returnValue;
    }

    /**
     * convert the data to webp of given quality
     * @param $quality
     * @param $out
     */
    protected function outputWEBP($quality, &$out)
    {
        //todo: switch between lossy/lossless
        $from = tempnam($this->cachePath, 'img');
        $to = tempnam($this->cachePath, 'webp');

        file_put_contents($from, $out);

        $command = $this->webpPath . ' -mt -q ' . min(($quality + $this->webpQualityFuzz), 100) . " $from -o $to";

        try {
            $returnValue = $this->generateOutput($command);

            if ($returnValue === 0) {
                $out = file_get_contents($to);
            } else {
                throw new \LogicException("webp returned: " . $returnValue);
            }
        } finally {
            unlink($from);
            unlink($to);
        }
    }

    /**
     * take the data and turn in to a png
     * @param $out
     */
    protected function outputPNG(&$out)
    {
        $from = tempnam($this->cachePath, 'img');
        file_put_contents($from, $out);

        $out = '';

        $command = $this->optipngPath . " -o5 $from";

        try {

            $returnValue = $this->generateOutput($command);

            if ($returnValue === 0) {
                $out = file_get_contents($from);
            } else {
                throw new \LogicException("png returned: " . $returnValue);
            }
        } finally {
            unlink($from);
        }
    }

    /**
     * take the data and convert to a jpeg
     * @param $quality
     * @param $out
     */
    protected function outputJPEG($quality, &$out)
    {

        $from = tempnam($this->cachePath, 'img');
        $to = tempnam($this->cachePath, 'jpg');

        file_put_contents($from, $out);
        $out = '';//make this small for now...

        $command = $this->mozjpegPath . " -quality " . min(($quality + $this->jpegQualityFuzz), 100) . " -optimize -progressive -outfile $to $from ";

        try {

            $returnValue = $this->generateOutput($command);

            if ($returnValue === 0) {
                $out = file_get_contents($to);
            } else {
                throw new \LogicException("jpeg returned: " . $returnValue);
            }

        } finally {
            unlink($from);
            unlink($to);
        }

    }


    /**
     * @param bool $verbose
     * @codeCoverageIgnore
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * @param string $debugOptions
     * @codeCoverageIgnore
     */
    public function setDebugOptions(string $debugOptions): void
    {
        $this->debugOptions = $debugOptions;
    }

    /**
     * @param mixed $debug
     * @codeCoverageIgnore
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @param int $webpQualityFuzz
     * @codeCoverageIgnore
     */
    public function setWebpQualityFuzz(int $webpQualityFuzz): void
    {
        $this->webpQualityFuzz = $webpQualityFuzz;
    }

    /**
     * @param int $jpegQualityFuzz
     * @codeCoverageIgnore
     */
    public function setJpegQualityFuzz(int $jpegQualityFuzz): void
    {
        $this->jpegQualityFuzz = $jpegQualityFuzz;
    }


}