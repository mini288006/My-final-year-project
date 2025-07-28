<?php

function createDatabase($connect, $dbname)
	{
		$createfile = file_get_contents("Tournament_Finder.sql", true);
		$queries = explode(";", $createfile);
		foreach($queries as $temp){
			try{
				$temp = trim($temp);
				if ($temp == "")
					continue;
				if(!$connect->query($temp))
				{
					// echo "query failed to run<br>";
					return 1;
				}
			} catch (Exception $e){
				echo "error while running query: $temp<br>";
				return 1;
			}
		}
		return 0;
	}
?>