# Full-Stack 功能测试 Spec

## Why
确保 app-dashboard 项目 100% 功能正常工作，包括最新在 test7 应用中所做的更新。需要端到端测试从创建新应用、登录管理员、创建集合、API密钥、用户、文件夹、权限设置、文件操作等所有功能。

## What Changes
- 创建一个全面的自动化测试脚本
- 覆盖所有功能模块的端到端测试
- 生成详细的测试报告

## Impact
- Affected specs: 全系统功能测试
- Affected code: dashboard、test7 应用、template 应用

## ADDED Requirements
### Requirement: 全功能自动化测试
系统 SHALL 提供一个自动化测试脚本，覆盖以下所有功能点。

### Requirement: 测试范围
测试 SHALL 覆盖以下模块：

#### 1. Dashboard 应用创建
- **WHEN** 通过 dashboard API 创建新应用
- **THEN** 应用文件夹被创建，数据库表被初始化，admin API key 被生成

#### 2. 应用文件夹配置
- **WHEN** 新应用被创建
- **THEN** config/database.php 和 config/settings.php 被正确更新

#### 3. Admin 登录
- **WHEN** 使用生成的 admin API key 登录
- **THEN** session 被正确创建，返回 admin 名称

#### 4. Collection 创建和 CRUD
- **WHEN** 创建、读取、更新、删除 collection 和 document
- **THEN** 操作成功，数据正确存储

#### 5. API Key 创建和管理
- **WHEN** 创建 API key、设置 admin、撤销 key
- **THEN** 操作成功，权限正确更新

#### 6. 用户创建和管理
- **WHEN** 创建用户、列出用户、删除用户
- **THEN** 操作成功

#### 7. 文件夹创建和管理
- **WHEN** 创建文件夹、重命名、删除文件夹
- **THEN** 操作成功，文件系统同步

#### 8. 权限设置
- **WHEN** 授予/撤销 collection 和 folder 权限
- **THEN** 权限正确生效

#### 9. 文件上传、移动、复制、删除
- **WHEN** 上传文件、移动文件、复制文件、删除文件
- **THEN** 操作成功，文件系统和数据库同步

#### 10. 健康检查和路由
- **WHEN** 访问健康端点和根路由
- **THEN** 返回正确响应

### Requirement: 测试报告
系统 SHALL 生成 JSON 和文本格式的测试报告，包含：
- 每个测试项的状态（PASS/FAIL）
- 错误详情
- 总体通过率
