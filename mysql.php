<?php

require_once 'db_config.php';

/*
 * @property-read int $error
 * @property-read int $insert_id
 * @property-read int $num_results
 *
 */

class MySql {

  const DUPLICATE_ENTRY = 1062;
  const INVALID_QUERY = 1064;
  const CONNECTION_CLOSED = -1;

  private static $instance = null;

  public static function instance() {
    if (!self::$instance) self::$instance = new MySql;
    return self::$instance;
  }

  private $link;
  private $last_query;
  private $stmt;

  private $error;
  private $insert_id;
  private $num_results;

  private static $read_only = ['error','insert_id','num_results'];


  private function __construct() {
    $this->link = mysqli_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PWD,MYSQL_DB);
  }

  /*
   * @param $query An sql select query.
   * @param $password_field  [field_name => value].
   * @param ...$params parameters to bind to the query (value only).
   * @return The user's row in the database if found, otherwise null.
   */
  public function auth(string $query,array $password_field,...$params) {
    $password_field_name = array_keys($password_field)[0];
    $password = array_values($password_field)[0];
    $result = $this->send_query($query,...$params);
    if ($this->num_results === 1) {
      $user = $result->current();
      if (!isset($user[$password_field_name])) return null;
      if (password_verify($password,$user[$password_field_name]))
        return $user;
    }
    return null;
  }

  /*
   * @param $query An sql query.
   * @param ...$params parameters to bind to the query.
   * @return 
   *    false if operation failed (sets error numnber in $error).
   *    true on success for operation that doesn't require results
   *        e.g. insert.
   *    null if result set is empty
   *    otherwise returns a generator of the result set.
   */
  public function send_query(string $query,...$params) {
    if ($this->checkConnection()) return false;
    $this->num_results = 0;
    $this->error = 0;
    if ($params) return $this->send_prepared_statement($query,...$params);
    else return $this->send_simple_query($query);
  }
  public function __get($prop) {
    if (!in_array($prop,self::$read_only)) throw new Exception("You canno access '$prop'");
    $this->checkConnection();
    return $this->$prop;
  }


  private function get_init_stmt($query) {
    if (!$this->stmt || $query !== $this->last_query) {
      $this->close_stmt();
      $this->stmt = $this->link->prepare($query);
    }
    return $this->stmt;
  }

  private function checkConnection() {
    return self::$instance === null;
  }

  private function send_prepared_statement($query,...$params) {
    $types = str_repeat('s',count($params));
    $stmt = $this->get_init_stmt($query);
    if (!@$stmt->bind_param($types,...$params) || !$stmt->execute()) {
      $this->error = $stmt ? $stmt->errno : self::INVALID_QUERY;
      return false;
    }
    $last_query = $query;
    if ($stmt->affected_rows > 0) {
      $this->insert_id = $stmt->insert_id;
      return true;
    }
    $result = $stmt->get_result();
    return $this->result_generator($result,$stmt);
  }

  private function send_simple_query($query) {
    $result = $this->link->query($query);
    if (!$result) {
      $this->error = $this->link->errno;
      return false;
    }
    if ($result === true) {
      $this->insert_id = $this->link->insert_id;
      return true;
    }
    return $this->result_generator($result);
  }


  private function result_generator($result,$stmt=null) {
    $gen = function ($result,$stmt) {
      while ($row = $result->fetch_assoc()) yield $row;
      ($stmt ?? $result)->free_result();
    };
    if ($result->num_rows === 0) return null;
    $this->num_results = $result->num_rows;
    return $gen($result,$stmt);
  }

  public function close_stmt() {
    $this->checkConnection();
    if (!$this->stmt) return;
    $this->stmt->close();
    $this->stmt = null;
  }


  public function close() {
    $this->checkConnection;
    $this->close_stmt();
    $this->link->close();
    self::$instance = null;
    $this->error = self::CONNECTION_CLOSED;
  }


}

?>
