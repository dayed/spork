Original library: [Kriswallsmith/spork](https://github.com/kriswallsmith/spork "Kriswallsmith/spork")

Spork: PHP on a Fork
--------------------

The original library was written by Kriswallsmith but it has been stripped down for use in the phresque project.

Significant Changes:

* FIFO up/down pipe removed - Originally two (up/down) fifo files were created to send data to/from the parent child processes. This was not required for the phresque project and was quite wasteful to leave in especially when creating a large number of jobs
* Promises - The orignal library offered a little syntatic sugar for waiting on the child process to complete using deferred objects used extensively in asynchronous languages eg. JavaScript. Due to the non-asynchronous nature of PHP no additional functionality was added and in my opinion this feature just complicated an already complex task (PHP forking)


```php
<?php

$manager = new Spork\ProcessManager();
$fork = $manager->fork(function() {
    // do something in child process!
    return 'Hello from '.getmypid();
});

#Carry on in parent process or call:

if($fork->wait()->isSuccessful())
{
    echo 'Hurray!';
}else
{
    echo 'Uh oh! Exited with: ' . $fork->getExitStatus();
}

```

### Example: Upload images to your CDN

Feed an iterator into the process manager and it will break the job into
multiple batches and spread them across many processes.

```php
<?php

$files = new IteratorIterator(FilesystemIterator('/path/to/images', FilesystemIterator::SKIP_DOTS));

$manager->process($files, function(SplFileInfo $file) {
    // upload this file
});
```
