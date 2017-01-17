<?php

class database
{

    private $pdo;

    function __construct()
    {
        $dbconf = homefs::app('conf')->config()['db'];
        $this->pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s', $dbconf['host'], $dbconf['database']), $dbconf['login'], $dbconf['password']
        );
        if ($this->pdo)
        {
            $this->pdo->query("SET NAMES 'utf8'");
            return $this->pdo;
        }
    }

    public function query($query, $params = array(), $arr = null)
    {
        $res = $this->pdo->prepare($query);
        if ($res)
        {
            $res->execute($params);
            return $res->fetchAll($arr ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
        }
        else
            return null;
    }

    public function query_found_rows($query, $params = array(), $arr = null)
    {
        $res = $this->pdo->prepare($query);
        if ($res)
        {
            $res->execute($params);
            $rows = $this->pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
            return array($rows, $res->fetchAll($arr ? PDO::FETCH_NUM : PDO::FETCH_ASSOC));
        }
        else
            return null;
    }

    public function query_single_col($query, $params = array())
    {
        $res = $this->pdo->prepare($query);
        if ($res)
        {
            $res->execute($params);
            $val = $res->fetch(PDO::FETCH_NUM);
            return $val[0];
        }
        else
            return null;
    }

    public function query_single_row($query, $params = array(), $arr = null)
    {
        $res = $this->pdo->prepare($query);
        if ($res)
        {
            $res->execute($params);
            $val = $res->fetch($arr ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
            return $val;
        }
        else
            return null;
    }

}

?>