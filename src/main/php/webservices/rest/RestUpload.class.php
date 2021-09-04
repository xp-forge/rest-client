<?php namespace webservices\rest;

use io\streams\{InputStream, OutputStream};
use util\MimeType;
use webservices\rest\io\Parts;

class RestUpload {
  const BOUNDARY = '---------------boundary1xp6132872336bc4';

  private $endpoint, $parts;

  /**
   * Creates a new upload instance
   *
   * @see    webservices.rest.RestResource::upload()
   * @param  webservices.rest.Endpoint $endpoint
   * @param  webservices.rest.RestRequest $request
   */
  public function __construct($endpoint, $request) {
    $this->endpoint= $endpoint;
    $this->parts= new Parts(self::BOUNDARY, $this->endpoint->open($request->with([
      'Content-Type'      => 'multipart/form-data; boundary='.self::BOUNDARY,
      'Transfer-Encoding' => 'chunked'
    ])));
  }

  /**
   * Pass a given parameter
   *
   * @param  string $name
   * @param  string $value
   * @return self
   */
  public function pass($name, $value) {
    $this->parts->begin(["Content-Disposition: form-data; name=\"{$name}\""]);
    $this->parts->write($value);
    return $this;
  }

  /**
   * Transfer a given stream
   *
   * @param  string $name
   * @param  io.streams.InputStream $in
   * @param  string $filename
   * @param  ?string $mime Uses `util.MimeType` if omitted
   * @return self
   */
  public function transfer($name, InputStream $in, $filename, $mime= null) {
    $this->parts->begin([
      "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"",
      'Content-Type: '.($mime ?? MimeType::getByFilename($filename))
    ]);
    while ($in->available()) {
      $this->parts->write($in->read());
    }
    return $this;
  }

  /**
   * Return a stream for writing
   *
   * @param  string $name
   * @param  string $filename
   * @param  ?string $mime Uses `util.MimeType` if omitted
   * @return io.streams.OutputStream
   */
  public function stream($name, $filename, $mime= null): OutputStream {
    $this->parts->begin([
      "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"",
      'Content-Type: '.($mime ?? MimeType::getByFilename($filename))
    ]);
    return $this->parts;
  }

  /**
   * Finish uploading and return response
   *
   * @return webservices.rest.RestResponse
   */
  public function finish() {
    return $this->endpoint->finish($this->parts->finalize());
  }
}