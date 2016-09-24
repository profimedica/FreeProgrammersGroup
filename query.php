<?php

class DataLayer{ 
	public function __construct(&$queryResult) {
	} 

	function GetConnection()
	{
        $mysqli = new mysqli("localhost","user_name", "user_password", "database_name");
		mysqli_set_charset($mysqli, 'utf8');
		if (mysqli_connect_errno())
		{
			die("Failed to connect to MySQL: " . mysqli_connect_error());
		}
		return $mysqli;
	}

	function UpdateUserSkill(&$queryResult, $table, $user_id, $skill_id, $r1, $r2, $r3, $v1, $v2, $v3)
	{
                if($user_id != $_SESSION["login_id"])
                {
                        return '{ "sql" : { "query" : "User can only change his own data! '.$_SESSION["login_id"].'" } , "data" : {  }}';
	        }
		
                $mysqli = $this->GetConnection();
		$sql=
<<<EOT
		INSERT INTO `user_{$table}`
			( `user_id`, `skill_id`, `r1`, `r2`, `r3`, `v1`, `v2`, `v3`) 
		VALUES 
			( {$user_id}, {$skill_id}, {$r1}, {$r2}, {$r3}, {$v1}, {$v2}, {$v3} )
		ON DUPLICATE KEY
			UPDATE `r1`={$r1}, `r2`={$r2}, `r3`={$r3}, `v1`={$v1}, `v2`={$v2}, `v3`={$v3};
EOT;
		$mysqli->real_query($sql);

		$this->info = $info;
        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
    }

	function GetObject(&$queryResult, $tableName) {

		$mysqli = $this->GetConnection();

		$pID = 0;
		$sql=
<<<EOT
		SELECT parents.`name` as pName, childs.`parent_id` as pID, childs.`id` as cID, childs.`name` as cName 
		FROM {$tableName} as parents 
		JOIN {$tableName} as childs 
		WHERE childs.`parent_id` = parents.`id` 
		ORDER BY childs.`parent_id` ASC
EOT;
		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {
				if($row[pID] != $pID)
				{
					$pID = $row['pID'];
					$queryResult->data[] = [ $row['pName'] => [ 'id' => $pID, 'skills' => null ] ];
				}
				$queryResult->data[0][$row['pName']]['skills'][] = [$row['cName'] => [ 'id' => $row['cID']]];
			}
			$result->close();
		}
		$mysqli->close();
	        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
        }

        // Display table on wellcome page
	function GetAllUsersSkills(&$queryResult, $tableName) {

		$mysqli = $this->GetConnection();

		$pID = 0; $son = '';

				$sql=
<<<EOT
		SELECT 
			user.`id`, 
			user.`name`,
			IF(ISNULL(user_skill_2.r3), 0, user_skill_2.r3) AS `SQL`, 
			IF(ISNULL(user_skill_18.r3), 0, user_skill_18.r3) AS `PHP`, 
			IF(ISNULL(user_skill_17.r3), 0, user_skill_17.r3) AS `CS`, 
			IF(ISNULL(user_skill_14.r3), 0, user_skill_14.r3) AS `JAVA`, 
			IF(ISNULL(user_skill_13.r3), 0, user_skill_13.r3) AS `CPP`, 
			IF(ISNULL(user_skill_20.r3), 0, user_skill_20.r3) AS `RUBY`, 
			IF(ISNULL(user_skill_16.r3), 0, user_skill_16.r3) AS `R`
		FROM
			user 
		LEFT JOIN (SELECT * FROM user_skill WHERE user_skill.skill_id=2) as user_skill_2 on user.id = user_skill_2.user_id
		LEFT JOIN (SELECT * FROM user_skill WHERE user_skill.skill_id=18) as user_skill_18 on user.id = user_skill_18.user_id
		LEFT JOIN (SELECT * FROM user_skill WHERE user_skill.skill_id=17) as user_skill_17 on user.id = user_skill_17.user_id
		LEFT JOIN (SELECT * FROM user_skill WHERE user_skill.skill_id=14) as user_skill_14 on user.id = user_skill_14.user_id
		LEFT JOIN (SELECT * FROM user_skill WHERE user_skill.skill_id=13) as user_skill_13 on user.id = user_skill_13.user_id
		LEFT JOIN (SELECT * FROM user_skill WHERE user_skill.skill_id=20) as user_skill_20 on user.id = user_skill_20.user_id
		LEFT JOIN (SELECT * FROM user_skill WHERE user_skill.skill_id=16) as user_skill_16 on user.id = user_skill_16.user_id
EOT;
		if ($result = $mysqli->query($sql)) {
		        while($row = mysqli_fetch_array($result)) {
				$queryResult->data[] = [ $row['id'], 'User_'.$row['id'], $row['SQL'], $row['PHP'], $row['CS'], $row['JAVA'], $row['CPP'], $row['RUBY'], $row['R'] ];
			}
			$result->close();                    
		}
		$mysqli->close();
	        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
        }



	function GetUserSkills(&$queryResult, $tableName, $user_id)
	{
		$mysqli = $this->GetConnection();
                $visibility = isset($_SESSION["login_id"]) ? 5 : 6;
		$pID = 0;
		$sql = 
<<<EOT
		SELECT 
			user.`name` , user.`id` , skill.`pName` AS pName, skill.`pID` AS pID, skill.`cID` AS cID, skill.`cName` AS cName, user_skill.skill_id AS skill_id, IF(user_skill.v1 >= {$visibility}, user_skill.r1, 0) AS r1, IF(user_skill.v2 >= {$visibility}, user_skill.r2, 0) AS r2, IF(user_skill.v3 >= {$visibility}, user_skill.r3, 0) AS r3
		FROM user
		JOIN user_skill 
			ON user.`id` = user_skill.`user_id` AND  user.`id` = {$user_id}
		JOIN
		(
			SELECT 
				parent.`name` AS pName, child.`parent_id` AS pID, child.`id` AS cID, child.`name` AS cName
			FROM skill AS parent
			JOIN skill AS child 
				ON child.`parent_id` = parent.`id`
		) AS skill 
			ON skill.cID = user_skill.skill_id
		ORDER BY pID;
EOT;

		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {
				if($row[pID] != $pID)
				{
					$pID = $row['pID'];
					$queryResult->data[] = [ 'User_'.$row['pId'] => [ 'id' => $pID, 'skills' => [] ] ];
				}
				$queryResult->data[0][$row['pName']]['skills'][] = [$row['cName'] => [ 'id' => $row['cID'], 'r1' => $row['r1'], 'r2' => $row['r2'], 'r3' => $row['r3'] ] ];
			}
			$result->close();
		}
		$mysqli->close();
	    $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
    }

    function GetInteractions(&$queryResult)
    {
                $mysqli = $this->GetConnection();
                $visibility = isset($_SESSION["login_id"]) ? 5 : 6;
		$sql = 
<<<EOT
SELECT 
    user.name AS user_name, 
    skill.name AS skill_name, 
    skill.id AS skill_id, 
    user.id AS user_id, 
    user_skill.r3 AS r1, 
    self.r2 AS r2 
FROM 
	user_skill 
JOIN 
	user 
	ON user.id = user_skill.user_id AND user_skill.r3 >1 
JOIN 
	skill 
	ON skill.id = user_skill.skill_id 
JOIN 
	user_skill AS self 
	ON self.`user_id` = 2 AND self.r2 > 1 AND user_skill.skill_id = self.skill_id AND user_skill.user_id <> self.user_id
ORDER BY skill.id ASC 
EOT;

		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {
				if($row['skill_id'] != $pID)
				{
					$pID = $row['skill_id'];
					$queryResult->data[] = [ $row['skill_name'] => [ 'id' => $row['skill_id'], 'skills' => null ] ];
                }
                $queryResult->data[0][$row['skill_name']]['skills'][] = ['User_'.$row['user_id'] => [ 'id' => $row['user_id'], 'r1' => $row['r1'], 'r2' => $row['r2']]];
			}
			$result->close();
		}
		$mysqli->close();
                $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
        }


	function GetAllSkillsWithUserData(&$queryResult, $tableName, $user_id) {

		$mysqli = $this->GetConnection();

		$pID = 0;
		$sql = 
<<<EOT
		SELECT 
			user.`name` , user.`id` , skill.`pName` AS pName, skill.`pID` AS pID, skill.`cID` AS cID, skill.`cName` AS cName, user_skill.skill_id AS skill_id, user_skill.r1, user_skill.r2, user_skill.r3, user_skill.v1, user_skill.v2, user_skill.v3, skill.`pUrl` AS pUrl, skill.`cUrl` AS cUrl 
		FROM user
		LEFT JOIN user_{$tableName} AS user_skill
			ON user.`id` = user_skill.`user_id` AND  user.`id` ={$user_id}
		RIGHT JOIN
		(
			SELECT 
				child.`parent_id` AS pID, parent.`name` AS pName, parent.`url` AS pUrl, child.`id` AS cID, child.`name` AS cName, child.`url` AS cUrl
			FROM {$tableName} AS parent
			JOIN {$tableName} AS child 
				ON child.`parent_id` = parent.`id`
		) AS skill 
			ON skill.cID = user_skill.skill_id
		ORDER BY pID;	
EOT;

		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {
				if($row[pID] != $pID)
				{
					$pID = $row['pID'];
					$json.=substr($son, 2).'}}, "'.$row['pName'].'" : { "id" : '.$pID.', "url" : "'.$row['pUrl'].'", "skills":{';
					$son = '';
				}
				$son.=', "'.$row['cName'].'" : { "id" : '.$row['cID'].', "r1" : '.($row['r1'] == NULL ? 0 : $row['r1']).', "r2" : '.($row['r2'] == NULL ? 0 : $row['r2']).', "r3" : '.($row['r3'] == NULL ? 0 : $row['r3']).', "v1" : '.($row['v1'] == NULL ? 0 : $row['v1']).', "v2" : '.($row['v2'] == NULL ? 0 : $row['v2']).', "v3" : '.($row['v3'] == NULL ? 0 : $row['v3']).', "url" : "'.$row['cUrl'].'" }';
			}
			$json.=substr($son, 2).'}}';
			$result->close();
		}
		$mysqli->close();
	        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
        }
}

class QueryResult implements JsonSerializable {
    
    public $data;
    public $sql;
    public $query;

    public function __construct($processed_sql_statement) {
        $this->sql = $processed_sql_statement;
    }

    public function jsonSerialize() {
        $this->content = 
        [
                'sql' => [ 'query' => $this->query ],
               'data' => $this->data
        ];
        return $this->content;
    }
}

if(isset($_GET['interactions']))
{
    $queryResult = new QueryResult('Get all interactions');
    $dataLayer = new DataLayer($queryResult);
    $dataLayer->GetInteractions($queryResult);
    echo json_encode($queryResult, JSON_PRETTY_PRINT);
}
else if(isset($_GET['hierarchy']))
{
    $queryResult = new QueryResult('Get all interactions');
    $dataLayer = new DataLayer($queryResult);
    $dataLayer->GetObject($queryResult, 'skill');
    echo json_encode($queryResult, JSON_PRETTY_PRINT); 
}
else if(isset($_GET['skills']))
{
    $queryResult = new QueryResult('Get all interactions');
    $dataLayer = new DataLayer($queryResult);
    $dataLayer->GetAllUsersSkills($queryResult, 'skill');
    echo json_encode($queryResult, JSON_PRETTY_PRINT); 
}
else if(isset($_GET['user']))
{
    $queryResult = new QueryResult('Get all interactions');
    $dataLayer = new DataLayer($queryResult);
    $dataLayer->GetUserSkills($queryResult, 'skill', 1);
    echo json_encode($queryResult, JSON_PRETTY_PRINT); 
}
else if(isset($_GET['all']))
{
    $queryResult = new QueryResult('Get all interactions');
    $dataLayer = new DataLayer($queryResult);
    $dataLayer->GetAllSkillsWithUserData($queryResult, 'skill', 1);
    echo json_encode($queryResult, JSON_PRETTY_PRINT); 
}
else
{
    echo 
<<<EOT
<pre>
<h3 style='color: blue'>A generic API that include debug information</h3>
<b>Database description:</b>
Users have skills. Skills belong to a domain.

<b>One-to-One relationsheep</b>
Each skill has a description. No other skill can have the same description. Otherwise it will be very confusing.
Ex. 'MySQL' is described as 'Open-source relational database management system provided by Oracle as "MySQL"'.

<b>One-to-Many relationsheep</b>
A domain can have many skills. But a skill can only belong to a single domain.
Ex. 'MySQL' and 'SqLite' belong only to 'SQL' domain.

<b>Many-to-Many relationsheep</b>
A user can have many skills. And many users can have the same skill.
Ex. I know 'MySQL' and 'SqLite'. But you also know 'MySql'.

</pre>
EOT
;
    echo "<ol>";
    echo "    <li>Query  <a href='http://".$_SERVER['SERVER_NAME']."/query.php?interactions'>Many to Many</a> Match users by skills</li>";
    echo "    <li>Query  <a href='http://".$_SERVER['SERVER_NAME']."/query.php?hierarchy'>Hierarchical</a> Skills in each Domain</li>";
    echo "    <li>Query  <a href='http://".$_SERVER['SERVER_NAME']."/query.php?skills'>OneToMany</a> All Users summary</li>";
    echo "    <li>Query  <a href='http://".$_SERVER['SERVER_NAME']."/query.php?user'>ManyToMany</a> Skills of a given User</li>";
    echo "    <li>Query  <a href='http://".$_SERVER['SERVER_NAME']."/query.php?all'>OneToMany</a> </li>";
    echo "</ol>";
}

?>

