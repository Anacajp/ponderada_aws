<?php include "../inc/dbinfo.inc"; ?>
<html>
<head>
    <title>Sistema de Cadastro de Funcionários</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #2c3e50; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #3498db; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border: 1px solid #ddd; }
        input[type=text], input[type=number], input[type=date] { padding: 6px; margin: 4px 0; width: 90%; }
        input[type=submit] { background-color: #2ecc71; color: white; padding: 8px 15px; border: none; cursor: pointer; }
    </style>
</head>
<body>
<h1>Cadastro de Funcionários</h1>
<?php
  /* Connect to MySQL and select the database. */
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
  if (mysqli_connect_errno()) echo "Falha na conexão com MySQL: " . mysqli_connect_error();
  $database = mysqli_select_db($connection, DB_DATABASE);
  
  /* Ensure that the EMPLOYEES table exists. */
  VerifyEmployeesTable($connection, DB_DATABASE);
  
  /* If input fields are populated, add a row to the EMPLOYEES table. */
  if (isset($_POST['submit'])) {
    $employee_name = htmlentities($_POST['NAME']);
    $employee_salary = isset($_POST['SALARY']) ? floatval($_POST['SALARY']) : 0;
    $employee_birthdate = htmlentities($_POST['BIRTHDATE']);
    $employee_active = isset($_POST['ACTIVE']) ? 1 : 0;
    
    if (!empty($employee_name) && $employee_salary > 0 && !empty($employee_birthdate)) {
      AddEmployee($connection, $employee_name, $employee_salary, $employee_birthdate, $employee_active);
    }
  }
?>
<!-- Input form -->
<form action="<?PHP echo $_SERVER['SCRIPT_NAME'] ?>" method="POST">
  <table border="0">
    <tr>
      <th>Nome</th>
      <th>Salário (R$)</th>
      <th>Data de Nascimento</th>
      <th>Funcionário Ativo</th>
      <th></th>
    </tr>
    <tr>
      <td>
        <input type="text" name="NAME" maxlength="45" size="30" required />
      </td>
      <td>
        <input type="number" name="SALARY" step="0.01" min="0" required />
      </td>
      <td>
        <input type="date" name="BIRTHDATE" required />
      </td>
      <td>
        <input type="checkbox" name="ACTIVE" checked />
      </td>
      <td>
        <input type="submit" name="submit" value="Adicionar Funcionário" />
      </td>
    </tr>
  </table>
</form>

<!-- Display table data. -->
<h2>Funcionários Cadastrados</h2>
<table border="1">
  <tr>
    <th>ID</th>
    <th>Nome</th>
    <th>Salário (R$)</th>
    <th>Data de Nascimento</th>
    <th>Status</th>
  </tr>
<?php
$result = mysqli_query($connection, "SELECT * FROM EMPLOYEES ORDER BY ID DESC");
if ($result) {
  while($query_data = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>", $query_data['ID'], "</td>",
         "<td>", $query_data['NAME'], "</td>",
         "<td>R$ ", number_format($query_data['SALARY'], 2, ',', '.'), "</td>",
         "<td>", date('d/m/Y', strtotime($query_data['BIRTHDATE'])), "</td>",
         "<td>", ($query_data['ACTIVE'] == 1 ? 'Ativo' : 'Inativo'), "</td>";
    echo "</tr>";
  }
  mysqli_free_result($result);
} else {
  echo "<tr><td colspan='5'>Erro ao recuperar dados: " . mysqli_error($connection) . "</td></tr>";
}
?>
</table>
<!-- Clean up. -->
<?php
  mysqli_close($connection);
?>
</body>
</html>
<?php
/* Add an employee to the table. */
function AddEmployee($connection, $name, $salary, $birthdate, $active) {
   $n = mysqli_real_escape_string($connection, $name);
   $s = floatval($salary);
   $b = mysqli_real_escape_string($connection, $birthdate);
   $a = intval($active);
   
   $query = "INSERT INTO EMPLOYEES (NAME, SALARY, BIRTHDATE, ACTIVE) VALUES ('$n', $s, '$b', $a);";
   if(!mysqli_query($connection, $query)) {
     echo("<p>Erro ao adicionar funcionário: " . mysqli_error($connection) . "</p>");
   }
}

/* Check whether the table exists and, if not, create it. */
function VerifyEmployeesTable($connection, $dbName) {
  if(!TableExists("EMPLOYEES", $connection, $dbName)) {
     $query = "CREATE TABLE EMPLOYEES (
         ID int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
         NAME VARCHAR(45) NOT NULL,
         SALARY DECIMAL(10,2) NOT NULL,
         BIRTHDATE DATE NOT NULL,
         ACTIVE TINYINT(1) NOT NULL DEFAULT 1
       )";
     if(!mysqli_query($connection, $query)) {
       echo("<p>Erro ao criar tabela: " . mysqli_error($connection) . "</p>");
     }
  } else {
    // Check if the table structure needs to be updated
    $result = mysqli_query($connection, "SHOW COLUMNS FROM EMPLOYEES LIKE 'SALARY'");
    if (mysqli_num_rows($result) == 0) {
      // Update table structure if it's using the old format
      $query = "ALTER TABLE EMPLOYEES 
                DROP COLUMN ADDRESS,
                ADD COLUMN SALARY DECIMAL(10,2) NOT NULL AFTER NAME,
                ADD COLUMN BIRTHDATE DATE NOT NULL AFTER SALARY,
                ADD COLUMN ACTIVE TINYINT(1) NOT NULL DEFAULT 1 AFTER BIRTHDATE";
      if(!mysqli_query($connection, $query)) {
        echo("<p>Erro ao atualizar estrutura da tabela: " . mysqli_error($connection) . "</p>");
      }
    }
  }
}

/* Check for the existence of a table. */
function TableExists($tableName, $connection, $dbName) {
  $t = mysqli_real_escape_string($connection, $tableName);
  $d = mysqli_real_escape_string($connection, $dbName);
  $checktable = mysqli_query($connection,
      "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_NAME = '$t' AND TABLE_SCHEMA = '$d'");
  if(mysqli_num_rows($checktable) > 0) return true;
  return false;
}
?>