<?php namespace webservices\rest\io;

class Streamed extends Transfer {

  /**
   * Start streaming
   *
   * @param  peer.http.HttpConnection $conn
   * @param  peer.http.HttpRequest $request
   * @return io.streams.OutputStream
   */
  public function start($conn, $request) {
    $request->setHeader('Transfer-Encoding', 'chunked');
    $request->setHeader('Content-Length', null);
    return $conn->open($request);
  }

  /**
   * Finish streaming
   *
   * @param  peer.http.HttpConnection $conn
   * @param  peer.http.HttpRequest $request
   * @param  io.streams.OutputStream $output
   * @return peer.http.HttpResponse
   */
  public function finish($conn, $request, $output) {
    return $output ? $conn->finish($output) : $conn->send($request);
  }
}