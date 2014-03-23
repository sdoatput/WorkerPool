WorkerPool
==========

**Parallel Processing WorkerPool for PHP**

_This library is in its infancy. I am adding features to it as I require them._

## Examples

### WorkerPool

The WorkerPool class provides a very simple interface to pass data to a worker pool and have it processed.
You can at any time fetch the results from the workers. Each worker child can return any value that can be [serialized][serialize].

```php
<?php
require_once(__DIR__.'/src/QXSWorkerPool.php');

use QXS\WorkerPool;
use QXS\Worker;


/**
 * Our Worker Class
 */
Class MyWorker implements Worker {
        protected $sem;
        /**
         * after the worker has been forked into another process
         *
         * @param \QXS\WorkerPool\Semaphore $semaphore the semaphore to run synchronized tasks
         * @throws \Exception in case of a processing Error an Exception will be thrown
         */
        public function onProcessCreate(Semaphore $semaphore) {
                $this->sem=$semaphore;
                // write something to the stdout
                echo "\t[".getmypid()."] has been created.\n";
                // initialize mt_rand
                list($usec, $sec) = explode(' ', microtime());
                mt_srand( (float) $sec + ((float) $usec * 100000) );
        }
        /**
         * before the worker process is getting destroyed
         *
         * @throws \Exception in case of a processing Error an Exception will be thrown
         */
        public function onProcessDestroy() {
                // write something to the stdout
                echo "\t[".getmypid()."] will be destroyed.\n";
        }
        /**
         * run the work
         *
         * @param Serializeable $input the data, that the worker should process
         * @return Serializeable Returns the result
         * @throws \Exception in case of a processing Error an Exception will be thrown
         */
        public function run($input) {
                $input=(string)$input;
                echo "\t[".getmypid()."] Hi $input\n";
                sleep(mt_rand(0,10)); // this is the workload!
                // and sometimes exceptions might occur
                if(mt_rand(0,10)==9) {
                        throw new \RuntimeException('We have a problem for '.$input.'.');
                }
                return "Hi $input";
        }
}


$wp=new WorkerPool();
$wp->setWorkerPoolSize(10)
   ->create(new MyWorker());

// produce some tasks
for($i=1; $i<=50; $i++) {
        $wp->run($i);
}

// some statistics
echo "Busy Workers:".$wp->getBusyWorkers()."  Free Workers:".$wp->getFreeWorkers()."\n";

// wait for completion of all tasks
$wp->waitForAllWorkers();

// collect all the results
foreach($wp as $key => $val) {
        if(isset($val['data'])) {
                echo "RESULT $key: ".$val['data']."\n";
        }
        elseif(isset($val['workerException'])) {
                echo "WORKER EXCEPTION $key: ".$val['workerException']['class'].": ".$val['workerException']['message']."\n".$val['workerException']['trace']."\n";
        }
        elseif(isset($val['poolException'])) {
                echo "POOL EXCEPTION $key: ".$val['poolException']['class'].": ".$val['poolException']['message']."\n".$val['poolException']['trace']."\n";
        }
}


// write something, before the parent exits
echo "ByeBye\n";

```

  [serialize]: http://php.net/serialize