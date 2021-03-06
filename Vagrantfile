# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

hostname = '4klift.vm.deasil.works'

$provision_script = <<SCRIPT
    yum install -y centos-release-SCL scl-utils-build --enablerepo=extras

    rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm
    rpm -Uvh https://rpms.remirepo.net/enterprise/remi-release-6.rpm
    ln -sf /home/vagrant/project/dev/vm/datastax.repo /etc/yum.repos.d/datastax.repo

    yum -y install yum-utils
    /usr/bin/yum-config-manager --enable remi-php56
    /usr/bin/yum-config-manager --enable remi

    yum install -y figlet python27 java-1.8.0-openjdk datastax-ddc nginx gcc g++ make automake autoconf git
    yum install -y curl-devel openssl-devel zlib-devel httpd-devel apr-devel apr-util-devel sqlite-devel
    yum install -y gmp gmp-devel boost cmake libtool openssl-devel pcre-devel automake openssl-devel
    yum install -y ruby-rdoc ruby-devel rubygems
    yum install -y php-fpm php php-devel php-mysql php-mbstring php-xml pcre-devel
    yum install -y cassandra-cpp-driver cassandra-cpp-driver-devel libuv libuv-devel



    cd /usr/src/
    wget https://pecl.php.net/get/cassandra-1.3.0.tgz
    tar -xzvf cassandra-1.3.0.tgz
    cd /usr/src/cassandra-1.3.0
    phpize
    /usr/src/cassandra-1.3.0/configure
    make

    cd /usr/src/
    wget https://pecl.php.net/get/cassandra-1.2.2.tgz
    tar -xzvf cassandra-1.2.2.tgz
    cd /usr/src/cassandra-1.2.2
    phpize
    /usr/src/cassandra-1.2.2/configure
    make

    # switch drivers when needed
    ln -sf /home/vagrant/project/dev/vm/40-cassandra-v1.2.2.ini /etc/php.d/40-cassandra.ini

    rm -f /home/vagrant/.bashrc
    ln -sf /home/vagrant/project/dev/vm/bashrc /home/vagrant/.bashrc

    service cassandra start
    scl enable python27 "pip install cqlsh"

    service php-fpm start
    rm -f /etc/php-fpm.d/www.conf
    ln -sf /home/vagrant/project/dev/vm/www.conf /etc/php-fpm.d/www.conf
    service php-fpm restart

    rm -f /etc/nginx/nginx.conf
    ln -sf /home/vagrant/project/dev/vm/nginx.conf /etc/nginx/nginx.conf
    ln -sf /home/vagrant/project/dev/vm/4klift.conf /etc/nginx/conf.d/4klift.conf

    service nginx start

    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer

    curl -sSL https://rvm.io/mpapis.asc | sudo gpg2 --import -
    cd /usr/src
    curl -sSL get.rvm.io | bash -s stable
    source /etc/profile.d/rvm.sh
    rvm install 2.4.1
    rvm use 2.4.1 --default

    wget -q https://dl.yarnpkg.com/rpm/yarn.repo -O /etc/yum.repos.d/yarn.repo
    curl --silent --location https://rpm.nodesource.com/setup_6.x | bash -
    yum install -y nodejs yarn
    npm install -g bower
    npm install -g webpack
    gem install sass

    wget -q https://phar.phpunit.de/phpunit-5.7.phar -O /usr/local/bin/phpunit
    chmod 775 /usr/local/bin/phpunit
SCRIPT


Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

    config.vm.box = "bento/centos-6.7"
    config.vbguest.auto_update = false

    config.vm.provision "shell", inline: $provision_script
    config.vm.synced_folder ".", "/home/vagrant/project", disabled: false, type: "virtualbox"

    config.vm.network "private_network", ip: "192.168.222.11"
    config.vm.network "forwarded_port", guest: 80, host: 8080
    config.ssh.forward_agent = true

    config.vm.hostname = hostname

    config.vm.provider "virtualbox" do |v|
      host = RbConfig::CONFIG['host_os']

      # Give VM 2/4 system memory & access to all cpu cores on the host if mac or linux
      # otherwise 4 cpus and 2048 memory for all other platforms.

      if host =~ /darwin/
        cpus = `sysctl -n hw.ncpu`.to_i
        mem = `sysctl -n hw.memsize`.to_i / 1024 / 1024 / 2
      elsif host =~ /linux/
        cpus = `nproc`.to_i
        mem = `grep 'MemTotal' /proc/meminfo | sed -e 's/MemTotal://' -e 's/ kB//'`.to_i / 1024 / 2
      else
        cpus = 4
        mem = 2048
      end

      v.memory = mem
      v.cpus = cpus
    end

end

