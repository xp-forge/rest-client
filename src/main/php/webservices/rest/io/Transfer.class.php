<?php namespace webservices\rest\io;

abstract class Transfer {

  /**
   * Start transfer
   *
   * @param  peer.http.HttpConnection $conn
   * @param  peer.http.HttpRequest $request
   * @return io.streams.OutputStream
   */
  public abstract function start($conn, $request);

  /**
   * Finish transfer
   *
   * @param  peer.http.HttpConnection $conn
   * @param  peer.http.HttpRequest $request
   * @param  io.streams.OutputStream $output
   * @return peer.http.HttpResponse
   */
  public abstract function finish($conn, $request, $output);
}