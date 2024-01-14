<?php namespace webservices\rest\io;

use io\streams\MemoryOutputStream;

class Buffered extends Transfer {

  /**
   * Start buffering
   *
   * @param  peer.http.HttpConnection $conn
   * @param  peer.http.HttpRequest $request
   * @return io.streams.OutputStream
   */
  public function start($conn, $request) {
    return new MemoryOutputStream();
  }

  /**
   * Finish buffering
   *
   * @param  peer.http.HttpConnection $conn
   * @param  peer.http.HttpRequest $request
   * @param  io.streams.OutputStream $output
   * @return peer.http.HttpResponse
   */
  public function finish($conn, $request, $output) {
    if (null === $output) return $conn->send($request);
 
    $bytes= $output->bytes();
    $request->setHeader('Content-Length', strlen($bytes));

    $stream= $conn->open($request);
    $stream->write($bytes);
    return $conn->finish($stream);
  }
}