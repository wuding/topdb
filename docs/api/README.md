# Topdb

### Table

| #          | 说明             | 方法        | 已实现 |
| ---------- | ---------------- | ----------- | ------ |
| 基本       |                  |             |        |
|            |                  | __construct |        |
|            |                  | __call      |        |
|            |                  | getVars     |        |
|            |                  | inst        |        |
|            |                  | initAdapter |        |
|            |                  | init        |        |
| 覆盖       |                  |             |        |
|            |                  | exec        |        |
|            |                  | query       |        |
| 拼接       |                  |             |        |
|            |                  | from        |        |
|            |                  | sqlColumns  |        |
|            |                  | sqlSet      |        |
|            |                  | sqlWhere    |        |
| 结构和数据 |                  |             |        |
|            |                  | create      | N      |
|            |                  | alter       | N      |
|            |                  | drop        | N      |
| CRUD       |                  |             |        |
|            |                  | insert      | N      |
|            | 获取多行         | select      | N      |
|            |                  | update      |        |
|            |                  | delete      | N      |
| 批量或其他 |                  |             |        |
|            |                  | into        | N      |
|            | 获取单条         | get         |        |
|            | 根据 ID 获取单条 | find        | N      |
|            |                  | set         | N      |
|            |                  | del         | N      |
| 高级       |                  |             |        |
|            |                  | exists      | N      |
| 补充       |                  |             |        |
|            |                  | logs        |        |



## Methods Details

### alter()

```
ALTER TABLE tbl_name AUTO_INCREMENT=1;
```