<?php
use Kahlan\Filter\Filter;
use Kahlan\Reporter\Coverage;
use Kahlan\Reporter\Coverage\Driver\Xdebug;
use Kahlan\Reporter\Coverage\Exporter\Coveralls;
use Kahlan\Reporter\Coverage\Exporter\CodeClimate;

$args = $this->args();
$args->argument('coverage', 'default', 3);

Filter::register('kahlan.coverage', function($chain) {
    if (!extension_loaded('xdebug')) {
        return;
    }
    $reporters = $this->reporters();
    $coverage = new Coverage([
        'verbosity' => $this->args()->get('coverage'),
        'driver'    => new Xdebug(),
        'path'      => $this->args()->get('src'),
        'exclude'   => [
            //Exclude init script
            'src/init.php',
            //Exclude Workflow from code coverage reporting
            'src/Cli/Kahlan.php',
            //Exclude coverage classes from code coverage reporting (don't know how to test the tester)
            'src/Reporter/Coverage/Collector.php',
            'src/Reporter/Coverage/Driver/Xdebug.php',
            'src/Reporter/Coverage/Driver/HHVM.php',
            'src/Reporter/Coverage/Driver/Phpdbg.php',
            //Exclude text based reporter classes from code coverage reporting (a bit useless)
            'src/Reporter/Dot.php',
            'src/Reporter/Bar.php',
            'src/Reporter/Verbose.php',
            'src/Reporter/Terminal.php',
            'src/Reporter/Reporter.php',
            'src/Reporter/Coverage.php'
        ],
        'colors'    => !$this->args()->get('no-colors')
    ]);
    $reporters->add('coverage', $coverage);
});

Filter::apply($this, 'coverage', 'kahlan.coverage');

Filter::register('kahlan.coverage-exporter', function($chain) {
    $reporter = $this->reporters()->get('coverage');
    if (!$reporter) {
        return;
    }
    Coveralls::write([
        'collector'      => $reporter,
        'file'           => 'coveralls.json',
        'service_name'   => 'travis-ci',
        'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
    ]);
    CodeClimate::write([
        'collector'  => $reporter,
        'file'       => 'codeclimate.json',
        'branch'     => getenv('TRAVIS_BRANCH') ?: null,
        'repo_token' => 'a4b5637db5629f60a5d3fc1a070b2339479ff8989c6491dfc6a19cada5e4ffaa'
    ]);
    return $chain->next();
});

Filter::apply($this, 'reporting', 'kahlan.coverage-exporter');
