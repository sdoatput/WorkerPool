<?php

namespace QXS\WorkerPool;

class SemaphoreException extends \Exception { }

/**
Example:
$t=new Semaphore();
$t->create(Semaphore::SEM_FTOK_KEY);

$t->acquire();
echo "We are in the sem\n";
$t->release();

$t->synchronizedBegin();
echo "We are in the sem\n";
$t->synchronizedEnd();

$t->destroy();
 */
class Semaphore {
	const SEM_RAND_KEY='rand';
	const SEM_FTOK_KEY='ftok';
	protected $semaphore=null;
	protected $semKey=null;

	public function getSemaphoreKey() {
		return $this->semKey;
	}
	
	public function create($semKey=Semaphore::SEM_FTOK_KEY, $maxAcquire=1) {
		if(is_resource($this->semaphore)) {
			throw new SemaphoreException('Semaphore has already been created.');
		}
		
		if(!is_int($maxAcquire)) {
			$maxAcquire=1;
		}

		// randomly generate semaphore, without collision
		if($semKey==Semaphore::SEM_RAND_KEY) {
			$retries=5;
		}
		else {
			$retries=1;
		}
		// try to generate a semaphore
		while(!is_resource($this->semaphore) && $retries>0) {
			$retries--;
			// generate a semKey
			if(!is_int($semKey)) {
				if($semKey==Semaphore::SEM_RAND_KEY) {
					$this->semKey=mt_rand(1 , PHP_INT_MAX);
				}
				else {
					$this->semKey=ftok(__FILE__, 's');
				}
			}
			else {
				$this->semKey=$semKey;
			}
			$this->semaphore=sem_get($this->semKey, $maxAcquire, 0666, 0);
		}
		if(!is_resource($this->semaphore)) {
			$this->semaphore=null;
			$this->semKey=null;
			throw new SemaphoreException('Cannot create the semaphore.');
		}

		return $this;
	}

	public function acquire() {
		if(!@sem_acquire($this->semaphore)) {
			throw new SemaphoreException('Cannot acquire the semaphore.');
		}
		return $this;
	}

	public function release() {
		if(!@sem_release($this->semaphore)) {
			throw new SemaphoreException('Cannot release the semaphore.');
		}
		return $this;
	}

	public function synchronizedBegin() { return $this->acquire(); }
	public function synchronizedEnd() { return $this->release(); }

	public function destroy() {
		if(!is_resource($this->semaphore)) {
			throw new SemaphoreException('Semaphore hasn\'t yet been created.');
		}
		if(!sem_remove($this->semaphore)) {
			throw new SemaphoreException('Cannot remove the semaphore.');
		}

		$this->semaphore=null;
		$this->semKey=null;
		return $this;
	}

}
