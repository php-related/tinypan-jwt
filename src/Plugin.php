<?php

namespace tinypan\jwt;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected IOInterface $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $io->write('tinypan/jwt 插件激活成功2！');
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // 插件停用时执行的代码（一般清理操作）
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // 插件卸载时执行的代码（一般清理操作）
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => 'onPackageInstallOrUpdate',
            'post-package-update' => 'onPackageInstallOrUpdate',
        ];
    }

    public function onPackageInstallOrUpdate(PackageEvent $event)
    {
        $operation = $event->getOperation();

        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            return;
        }

        // 改成你自己包名
        if ($package->getName() === 'tinypan/jwt') {
            $this->io->write('tinypan/jwt 已成功安装或更新！');
        }
    }
}
