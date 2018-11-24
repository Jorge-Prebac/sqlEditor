<?php
/**
 * Con este plugin podrás ejecutar sentencias sql que muestren datos en pantalla
 * sentencias tipo select, show, etc.
 * Filtrará para evitar la ejecución de sentencias tipo Drop, delete, alter, etc.
 * @author Raul Jiménez <raljopa@gmail.com>
 */
namespace FacturaScripts\Plugins\SqlEditor\Controller;

use FacturaScripts\Core\Base;

class SqlEditor extends \FacturaScripts\Core\Base\Controller
{

    public $query;
    public $results;

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'sql Editor';
        $pageData['menu'] = 'develop';
        $pageData[icon] = 'fas fa-database';

        return $pageData;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permisions)
    {
        parent::privateCore($response, $user, $permisions);
        $this->setTemplate('sqlEditor');

        $query = $this->request->get('query', '');

        if ($query !== '') {
            $this->query = $query;
            if ($this->validQuery()) {
                $this->execQuery();
            } else {
                $this->miniLog->alert('invalid-sentence');
            }
        }
    }

    /**
     * Return true is is authorized query
     */
    private function validQuery()
    {
        $validSentences = ['select', 'show', 'describe', 'explain'];
        $invalidSentences = ['drop', 'delete', 'insert', 'update'];


        foreach ($invalidSentences as $sentence) {
            if (strpos($this->query, $sentence) > 0)
                return false;
        }

        foreach ($validSentences as $sentence) {

            if (strpos($this->query, $sentence) >= 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns array width result values of exec query
     * @param string $query
     */
    private function execQuery()
    {
        $dataBase = new \FacturaScripts\Core\Base\DataBase();
        $data = $dataBase->select($this->query);

        if ($data) {
            $this->results = $data;
        } else {
            $this->miniLog->notice('without-results');
        }
    }

    /**
     * Returns array width columns title of results
     * @return Array
     */
    public function columnsTitle()
    {
        return empty($this->results) ? [] : array_keys($this->results[0]);
    }
}
