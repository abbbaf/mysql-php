# MySQL PHP module.

A module for easily creating secure SQL queries.

## How to use?

### 1. Create an instance of the database:

`$db = MySql::instance();`

### 2. Create an SQL query:

`$query = "SELECT * FROM users WHERE user=? and password=?";`

### 3. Send the query to the database:

`
$user = 'user';
$password = 'password';
$result = $db->send_query($query,$username,$password);`

The result of the query can be one of the following:

**true** - The operation was successful but the quert is not expected to return 
any row from the database (e.g. INSERT).

**generator** - The operation was successful and the database returned matching rows 
then the result is a generator of all matching rows.

**null** - The operation failed because no matching row was found.

**false** - The operation failed for other reasons. 
The error code is stored in $db->error.

**Error constants** -  MySql::DUPLICATE_ENTRY,  MySql::INVALID_QUERY,   MySql::CONNECTION_CLOSED
 

### MySql class properties and methods:

**$error** - Same as the errno in mysqli.

**$insert_id** - Same as insert_id in mysqli.

**$num_results** - The number of results.

**send_query(string $query,...$params)** - $params are the bounded variable in the prepared query.

**auth(string $query,array $password_field,...$params)**

The auth method is used to authenticate a user if the passwords are stored using bcrypt.

e.g.
```
<?php
$query = "SELECT * FROM users WHERE user=?"; //Do not include the password column here
$db = MySql::instance();
$user = $_POST['user'];
$pass = $_POST['password'];
$success = $db->auth($query,['password' => $pass],$user);
?>

<form>
  <input type="text" name="user">
  <input type="passwrod" name="password">
</form>
```
`

`







