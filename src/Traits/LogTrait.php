<?php

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/16 18:46
 */

namespace Sweeper\HelperPhp\Traits;

use Monolog\ErrorHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use ReflectionClass;

!defined('WWW_PATH') && define('WWW_PATH', str_replace('＼＼', '/', dirname(__DIR__, 4) . '/'));  // 定义站点目录
!defined('APP_PATH') && define('APP_PATH', $_SERVER['DOCUMENT_ROOT'] ?: WWW_PATH);              // 定义应用目录

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/16 19:02
 * @Path \app\common\traits\LogTrait
 * @mixin \Monolog\Logger
 */
trait LogTrait
{

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /** @var string 日志记录器名字 */
    private $loggerName;

    /** @var string 文件名 */
    private $filename;

    /** @var string 日志路径 */
    private $logPath;

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:09
     * @param string $methodName
     * @param array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        // 优先调用自己方法
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }
        // 调用父类
        if (is_callable([$this, $name])) {
            return parent::__call($name, $arguments);
        }
        // 调用 Logger 方法
        if (method_exists(Logger::class, $name)) {
            return $this->getLogger()->{$name}(...$arguments);
        }

        throw new \BadMethodCallException('Method no exists:' . $name);
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @return string|null
     */
    public function getLoggerName(): ?string
    {
        return $this->loggerName;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:22
     * @param string $loggerName
     * @return $this
     */
    public function setLoggerName(string $loggerName): self
    {
        $this->loggerName = $loggerName;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @return string|null
     */
    public function getLogPath(): ?string
    {
        return $this->logPath;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @param string $logPath
     * @return $this
     */
    public function setLogPath(string $logPath): self
    {
        $this->logPath = $logPath;

        return $this;
    }

    /**
     * @return \Monolog\Logger
     */
    public function getLogger(): Logger
    {
        if (!($this->logger instanceof Logger)) {
            $this->getDefaultLogger($this->getLoggerName(), $this->getFilename(), $this->getLogPath());
        }

        return $this->logger;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 18:47
     * @param \Monolog\Logger $logger
     * @return $this
     */
    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * 默认日志记录器
     * User: Sweeper
     * Time: 2023/7/27 13:47
     * @doc https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md
     * @param string|null $name
     * @param string|null $filename
     * @param string|null $logPath
     * @param bool        $registerErrorHandler
     * @return \Monolog\Logger
     * @example $logger->info('Welcome to Sweeper Test.', ['username' => 'sweeper']);
     */
    public function getDefaultLogger(string $name = null, string $filename = null, string $logPath = null, bool $registerErrorHandler = true): Logger
    {
        return $this->setLogger(static::getSpecificLogger($name ?? $this->getLoggerName(), $filename ?? $this->getFilename(), $logPath ?? $this->getLogPath(), $registerErrorHandler))->getLogger();
    }

    /**
     * 获取指定 Logger
     * User: Sweeper
     * Time: 2023/9/1 11:23
     * @param string|null $name
     * @param string|null $filename
     * @param string|null $logPath
     * @param bool        $registerErrorHandler
     * @return \Monolog\Logger
     */
    public static function getSpecificLogger(string $name = null, string $filename = null, string $logPath = null, bool $registerErrorHandler = true): Logger
    {
        /**
         * Handlers
         * Log to files and syslog
         * StreamHandler: Logs records into any PHP stream, use this for log files.
         * RotatingFileHandler: Logs records to a file and creates one log file per day. It will also delete files older than $maxFiles. You should use logrotate for high profile setups though, this is just meant as a quick and dirty solution.
         * SyslogHandler: Logs records to the syslog.
         * ErrorLogHandler: Logs records to PHP's error_log() function.
         * ProcessHandler: Logs records to the STDIN of any process, specified by a command.
         * Send alerts and emails
         * NativeMailerHandler: Sends emails using PHP's mail() function.
         * SymfonyMailerHandler: Sends emails using a symfony/mailer instance.
         * PushoverHandler: Sends mobile notifications via the Pushover API.
         * SlackWebhookHandler: Logs records to a Slack account using Slack Webhooks.
         * SlackHandler: Logs records to a Slack account using the Slack API (complex setup).
         * SendGridHandler: Sends emails via the SendGrid API.
         * MandrillHandler: Sends emails via the Mandrill API using a Swift_Message instance.
         * FleepHookHandler: Logs records to a Fleep conversation using Webhooks.
         * IFTTTHandler: Notifies an IFTTT trigger with the log channel, level name and message.
         * TelegramBotHandler: Logs records to a Telegram bot account.
         * HipChatHandler: Logs records to a HipChat chat room using its API. Deprecated and removed in Monolog 2.0, use Slack handlers instead, see Atlassian's announcement
         * SwiftMailerHandler: Sends emails using a Swift_Mailer instance. Deprecated and removed in Monolog 3.0. Use SymfonyMailerHandler instead.
         * Log specific servers and networked logging
         * SocketHandler: Logs records to sockets, use this for UNIX and TCP sockets. See an example.
         * AmqpHandler: Logs records to an AMQP compatible server. Requires the php-amqp extension (1.0+) or php-amqplib library.
         * GelfHandler: Logs records to a Graylog2 server. Requires package graylog2/gelf-php.
         * ZendMonitorHandler: Logs records to the Zend Monitor present in Zend Server.
         * NewRelicHandler: Logs records to a NewRelic application.
         * LogglyHandler: Logs records to a Loggly account.
         * RollbarHandler: Logs records to a Rollbar account.
         * SyslogUdpHandler: Logs records to a remote Syslogd server.
         * LogEntriesHandler: Logs records to a LogEntries account.
         * InsightOpsHandler: Logs records to an InsightOps account.
         * LogmaticHandler: Logs records to a Logmatic account.
         * SqsHandler: Logs records to an AWS SQS queue.
         * RavenHandler: Logs records to a Sentry server using raven. Deprecated and removed in Monolog 2.0, use sentry/sentry 2.x and the Sentry\Monolog\Handler class instead.
         * Logging in development
         * FirePHPHandler: Handler for FirePHP, providing inline console messages within FireBug.
         * ChromePHPHandler: Handler for ChromePHP, providing inline console messages within Chrome.
         * BrowserConsoleHandler: Handler to send logs to browser's Javascript console with no browser extension required. Most browsers supporting console API are supported.
         * Log to databases
         * RedisHandler: Logs records to a redis server's key via RPUSH.
         * RedisPubSubHandler: Logs records to a redis server's channel via PUBLISH.
         * MongoDBHandler: Handler to write records in MongoDB via a Mongo extension connection.
         * CouchDBHandler: Logs records to a CouchDB server.
         * DoctrineCouchDBHandler: Logs records to a CouchDB server via the Doctrine CouchDB ODM.
         * ElasticaHandler: Logs records to an Elasticsearch server using ruflin/elastica.
         * ElasticsearchHandler: Logs records to an Elasticsearch server.
         * DynamoDbHandler: Logs records to a DynamoDB table with the AWS SDK.
         * Wrappers / Special Handlers
         * FingersCrossedHandler: A very interesting wrapper. It takes a handler as a parameter and will accumulate log records of all levels until a record exceeds the defined severity level. At which point it delivers all records, including those of lower severity, to the handler it wraps. This means that until an error actually happens you will not see anything in your logs, but when it happens you will have the full information, including debug and info records. This provides you with all the information you need, but only when you need it.
         * DeduplicationHandler: Useful if you are sending notifications or emails when critical errors occur. It takes a handler as a parameter and will accumulate log records of all levels until the end of the request (or flush() is called). At that point it delivers all records to the handler it wraps, but only if the records are unique over a given time period (60seconds by default). If the records are duplicates they are simply discarded. The main use of this is in case of critical failure like if your database is unreachable for example all your requests will fail and that can result in a lot of notifications being sent. Adding this handler reduces the amount of notifications to a manageable level.
         * WhatFailureGroupHandler: This handler extends the GroupHandler ignoring exceptions raised by each child handler. This allows you to ignore issues where a remote tcp connection may have died but you do not want your entire application to crash and may wish to continue to log to other handlers.
         * FallbackGroupHandler: This handler extends the GroupHandler ignoring exceptions raised by each child handler, until one has handled without throwing. This allows you to ignore issues where a remote tcp connection may have died but you do not want your entire application to crash and may wish to continue to attempt logging to other handlers, until one does not throw an exception.
         * BufferHandler: This handler will buffer all the log records it receives until close() is called at which point it will call handleBatch() on the handler it wraps with all the log messages at once. This is very useful to send an email with all records at once for example instead of having one mail for every log record.
         * GroupHandler: This handler groups other handlers. Every record received is sent to all the handlers it is configured with.
         * FilterHandler: This handler only lets records of the given levels through to the wrapped handler.
         * SamplingHandler: Wraps around another handler and lets you sample records if you only want to store some of them.
         * NoopHandler: This handler handles anything by doing nothing. It does not stop processing the rest of the stack. This can be used for testing, or to disable a handler when overriding a configuration.
         * NullHandler: Any record it can handle will be thrown away. This can be used to put on top of an existing handler stack to disable it temporarily.
         * PsrHandler: Can be used to forward log records to an existing PSR-3 logger
         * TestHandler: Used for testing, it records everything that is sent to it and has accessors to read out the information.
         * HandlerWrapper: A simple handler wrapper you can inherit from to create your own wrappers easily.
         * OverflowHandler: This handler will buffer all the log messages it receives, up until a configured threshold of number of messages of a certain level is reached, after it will pass all log messages to the wrapped handler. Useful for applying in batch processing when you're only interested in significant failures instead of minor, single erroneous events.
         * Formatters
         * LineFormatter: Formats a log record into a one-line string.
         * HtmlFormatter: Used to format log records into a human readable html table, mainly suitable for emails.
         * NormalizerFormatter: Normalizes objects/resources down to strings so a record can easily be serialized/encoded.
         * ScalarFormatter: Used to format log records into an associative array of scalar values.
         * JsonFormatter: Encodes a log record into json.
         * WildfireFormatter: Used to format log records into the Wildfire/FirePHP protocol, only useful for the FirePHPHandler.
         * ChromePHPFormatter: Used to format log records into the ChromePHP format, only useful for the ChromePHPHandler.
         * GelfMessageFormatter: Used to format log records into Gelf message instances, only useful for the GelfHandler.
         * LogstashFormatter: Used to format log records into logstash event json, useful for any handler listed under inputs here.
         * ElasticaFormatter: Used to format log records into an Elastica\Document object, only useful for the ElasticaHandler.
         * ElasticsearchFormatter: Used to add index and type keys to log records, only useful for the ElasticsearchHandler.
         * LogglyFormatter: Used to format log records into Loggly messages, only useful for the LogglyHandler.
         * MongoDBFormatter: Converts \DateTime instances to \MongoDate and objects recursively to arrays, only useful with the MongoDBHandler.
         * LogmaticFormatter: Used to format log records to Logmatic messages, only useful for the LogmaticHandler.
         * FluentdFormatter: Used to format log records to Fluentd logs, only useful with the SocketHandler.
         * GoogleCloudLoggingFormatter: Used to format log records for Google Cloud Logging. It works like a JsonFormatter with some minor tweaks.
         * SyslogFormatter: Used to format log records in RFC 5424 / syslog format. This can be used to output a syslog-style file that can then be consumed by tools like lnav.
         * Processors
         * PsrLogMessageProcessor: Processes a log record's message according to PSR-3 rules, replacing {foo} with the value from $context['foo'].
         * LoadAverageProcessor: Adds the current system load average to a log record.
         * ClosureContextProcessor: Allows delaying the creation of context data by setting a Closure in context which is called when the log record is used
         * IntrospectionProcessor: Adds the line/file/class/method from which the log call originated.
         * WebProcessor: Adds the current request URI, request method and client IP to a log record.
         * MemoryUsageProcessor: Adds the current memory usage to a log record.
         * MemoryPeakUsageProcessor: Adds the peak memory usage to a log record.
         * ProcessIdProcessor: Adds the process id to a log record.
         * UidProcessor: Adds a unique identifier to a log record.
         * GitProcessor: Adds the current git branch and commit to a log record.
         * MercurialProcessor: Adds the current hg branch and commit to a log record.
         * TagProcessor: Adds an array of predefined tags to a log record.
         * HostnameProcessor: Adds the current hostname to a log record.
         */

        $class    = new ReflectionClass(static::class);
        $name     = $name ?: $class->getName();
        $filename = $filename ?: $class->getShortName();
        $logPath  = $logPath ?: APP_PATH . '/runtime/log';

        // 实例化一个日志实例, 参数是 channel name
        $logger               = new Logger($name);
        $streamHandlerConsole = new StreamHandler('php://stdout', Logger::DEBUG);                              // 控制台输出
        $infoFileHandler      = new RotatingFileHandler("{$logPath}/{$filename}.info.log", 7, Logger::INFO);   // INFO 等级文件处理器
        $errorFileHandler     = new RotatingFileHandler("{$logPath}/{$filename}.error.log", 7, Logger::ERROR); // ERROR 等级文件处理器

        $logger->pushHandler($streamHandlerConsole)
               ->pushHandler($infoFileHandler->setFormatter(new JsonFormatter()))// 入栈, 往 handler stack 里压入 StreamHandler 的实例
               ->pushHandler($errorFileHandler->setFormatter(new JsonFormatter()))
               ->pushHandler(new FirePHPHandler())
               ->pushHandler(new ChromePHPHandler())
               ->pushHandler(new BrowserConsoleHandler());

        /**
         * processor 日志加工程序，用来给日志添加额外信息.
         * 这里调用了内置的 UidProcessor 类和 ProcessIdProcessor 类.
         * 在生成的日志文件中, 会在最后面显示这些额外信息.
         */
        $logger->pushProcessor(new ProcessIdProcessor())
               ->pushProcessor(new MemoryUsageProcessor())
               ->pushProcessor(new MemoryPeakUsageProcessor())
               ->pushProcessor(new IntrospectionProcessor(Logger::WARNING))
               ->pushProcessor(function($record) {
                   $record['logTime'] = date('Y-m-d H:i:s');

                   return $record;
               });

        $registerErrorHandler && ErrorHandler::register($logger);

        /**
         * 设置记录到日志的信息.
         * 开始遍历 handler stack.
         * 先入后出, 后压入的最先执行. 所以先执行 FirePHPHandler, 再执行 StreamHandler
         * 如果设置了 ErrorLogHandler 的 $bubble = false, 会停止冒泡, StreamHandler 不会执行.
         * 第二个参数为数组格式, 通过使用使用上下文(context)添加了额外的数据.
         * 简单的处理器（比如StreamHandler）将只是把数组转换成字符串。而复杂的处理器则可以利用上下文的优点（如 FirePHP 则将以一种优美的方式显示数组）.
         * @example $logger->info('Welcome to Sweeper Test.', ['username' => 'sweeper']);
         */

        return $logger;
    }

}
