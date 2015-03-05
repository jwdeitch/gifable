<?php namespace Gifable\Commands;

use Gifable\Commands\Command;

use Gifable\Gif;
use Gifable\Services\RackspaceService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Support\Facades\Log;
use OpenCloud\ObjectStore\Constants\UrlType;
use OpenCloud\ObjectStore\Resource\DataObject;

class TranscodeGifCommand extends Command implements SelfHandling, ShouldBeQueued {

	use InteractsWithQueue, SerializesModels;

    public $gif;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(Gif $gif)
	{
        $this->gif = $gif;
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function handle()
	{
        // Calculate video bitrate based on image dimensions using the Kush Gauge
        $targetBitrate = round($this->gif->width * $this->gif->height * 30 * 4 * 0.07 / 1000);

        $outputFilePath = sys_get_temp_dir() . '/' . $this->gif->shortcode;

        // Transcode GIF to WebM and MP4
        exec('~/bin/ffmpeg -i "' . $this->gif->gif_http_url . '" -c:v libvpx -qmin 0 -qmax 50 -crf 5 -b:v ' . $targetBitrate . 'k -an ' . $outputFilePath . '.webm', $output);
        Log::error(print_r($output));
        exec('~/bin/ffmpeg -i "' . $this->gif->gif_http_url . '" -c:v libx264 -preset slow -crf 18 -an ' . $outputFilePath . '.mp4', $output);
        Log::error(print_r($output));

        // Upload WebM and MP4 files to Rackspace
        $rackspaceService = new RackspaceService();
        $webmDataObject = $rackspaceService->uploadFile($this->gif->shortcode . '.webm', $outputFilePath . '.webm');
        $mp4DataObject = $rackspaceService->uploadFile($this->gif->shortcode . '.mp4', $outputFilePath . '.mp4');

        // Save WebM and MP4 information to dataabse
        $this->gif->webm_http_url = $this->getUrlFromDataObject($webmDataObject);
        $this->gif->webm_https_url = $this->getUrlFromDataObject($webmDataObject, UrlType::SSL);
        $this->gif->webm_size = filesize($outputFilePath . '.webm');
        $this->gif->mp4_http_url = $this->getUrlFromDataObject($mp4DataObject);
        $this->gif->mp4_https_url = $this->getUrlFromDataObject($mp4DataObject, UrlType::SSL);
        $this->gif->mp4_size = filesize($outputFilePath . '.mp4');
        $this->gif->save();

        // Delete WebM and MP4 files
        unlink($outputFilePath . '.webm');
        unlink($outputFilePath . '.mp4');
	}

    /**
     * Get the HTTP/HTTPS URL for a DataObject.
     *
     * @return string
     */
    private function getUrlFromDataObject(DataObject $dataObject, $urlType = UrlType::CDN)
    {
        return $dataObject->getPublicUrl($urlType)->getScheme() . '://' . $dataObject->getPublicUrl($urlType)->getHost() . $dataObject->getPublicUrl($urlType)->getPath();
    }

}
