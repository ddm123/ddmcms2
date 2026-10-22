<?php

exit;
/**
* <!--
* 注：上面第2行的 exit; 千万不要删除，否则下面的数据库信息可能会被不良网民偷窥的危险！
* 不要自行添加这一行：<?xml version="1.0" encoding="utf-8"?>，因为系统已经自动加上了
* -->
*/
?>

<defines>
  <admin_path>{admin_path}</admin_path><!-- 后台路径模块名称,可任意设置以防止别人猜后台入口,仅允许是字母或数字的组合 -->
  <hash_key>{hash_key}</hash_key>
  <!-- 数据库配置 -->
  <db tablepre="{db_tablepre}"><!-- @tablepre 数据表前缀 -->
    <read><!-- 用于只读数据库主机 -->
      <driver>{db_driver}</driver>
      <host><![CDATA[{db_host}]]></host><!-- 数据库主机 -->
      <port>{db_port}</port><!-- 端口 -->
      <username><![CDATA[{db_username}]]></username><!-- 数据库用户名 -->
      <password><![CDATA[{db_password}]]></password><!-- 数据库密码 -->
      <dbname><![CDATA[{db_dbname}]]></dbname><!-- 数据库名称 -->
      <use_pconnect>0</use_pconnect><!-- 是否使用持久连接数据库 -->
      <character>utf8</character><!-- 数据库编码 -->
    </read>
    <write><!-- 用于只写数据库主机 -->
      <driver>{db_driver}</driver>
      <host><![CDATA[{db_host}]]></host><!-- 数据库主机 -->
      <port>{db_port}</port><!-- 端口 -->
      <username><![CDATA[{db_username}]]></username><!-- 数据库用户名 -->
      <password><![CDATA[{db_password}]]></password><!-- 数据库密码 -->
      <dbname><![CDATA[{db_dbname}]]></dbname><!-- 数据库名称 -->
      <use_pconnect>0</use_pconnect><!-- 是否使用持久连接数据库 -->
      <character>utf8</character><!-- 数据库编码 -->
    </write>
  </db>
  <cache>
    <driver>{cache_driver}</driver>
    <enable>true</enable><!-- 是否启用页面块(Block)缓存的总开头(不会作用到其它数据的缓存) -->
    <prefix>{cache_prefix}</prefix>
    <cache_dir>data/cache/data-cache</cache_dir>
  </cache>
  <session>
      <save_handler>Ddm\Session\File</save_handler>
  </session>
</defines>






