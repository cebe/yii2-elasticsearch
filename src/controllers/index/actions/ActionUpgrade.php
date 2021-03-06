<?php
namespace common\modules\elasticsearch\controllers\index\actions;

use common\modules\elasticsearch\contracts\Indexer;
use common\modules\elasticsearch\exceptions\SearchIndexerException;
use common\modules\elasticsearch\console\ConsoleAction;
use yii\console\Controller;

class ActionUpgrade extends ConsoleAction
{
    /** @var Indexer */
    private $indexer;

    /**
     * ActionRebuild constructor.
     * @param string $id
     * @param Controller $controller
     * @param Indexer $indexer
     * @param array $config
     */
    public function __construct(
        $id,
        Controller $controller,
        Indexer $indexer,
        array $config = []
    ) {
        $this->indexer = $indexer;
        parent::__construct($id, $controller, $config);
    }

    /** @inheritdoc */
    public function run()
    {
        try {
            $this->indexer->upgradeIndexes();
        } catch (SearchIndexerException $e) {
            $this->stdErr($e->getMessage());
            if($previous = $e->getPrevious()) {
                $this->stdDebug($previous->getMessage());
            }
        }
    }
}
