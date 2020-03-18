<style>
    .el-row {
        margin-bottom: 20px;
    }
</style>

<template>
    <el-tabs type="border-card">
        <el-tab-pane label="用户管理">
            <el-row>
                <el-col :span="4">
                    <el-input type="text" placeholder="请输入公众号名称" v-model="keyword" :rows="12" show-word-limit class="input-with-select">
                        <el-button slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </el-col>
                <el-col :span="12" :push="18">
                    <el-button type="primary" @click="getAuthQrcodeUrl()" round>获取公众号</el-button>
                </el-col>
            </el-row>
            <el-table
                    :data="tableData"
                    height="250"
                    :span-method="objectSpanMethod"
                    border
                    style="width: 100%"
                    max-height="900">
                <el-table-column
                        prop="date"
                        label="日期"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="name"
                        label="姓名"
                        width="180">
                </el-table-column>
                <el-table-column
                        prop="address"
                        label="地址">
                </el-table-column>
            </el-table>
        </el-tab-pane>
    </el-tabs>
</template>
<script>
    export default {
        data:function() {
            return {
                keyword:'',
                tableData: [{
                    date: '2016-05-03',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                }, {
                    date: '2016-05-02',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                }, {
                    date: '2016-05-04',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                },{
                    date: '2016-05-04',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                }, {
                    date: '2016-05-01',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                }, {
                    date: '2016-05-08',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                }, {
                    date: '2016-05-06',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                }, {
                    date: '2016-05-07',
                    name: '王小虎',
                    address: '上海市普陀区金沙江路 1518 弄'
                }]
            };
        },
        methods: {
            getCates(){
                let self = this;
                let info = {};

                self.$axios.post(self.$interfase.adminlogin, info).then(function (response){
                    let data = response.data.data;
                    if (data.length > 0) {
                    }
                });
            },
            objectSpanMethod({ row, column, rowIndex, columnIndex }) {
                if (columnIndex == 2) {
                    return {
                        rowspan: 2,
                        colspan: 1
                    };
                }
            },
            getAuthQrcodeUrl(){
                let self = this;
                self.$ajax.post(self.$interfase.getAuthQrcodeUrl).then(function (response){
                    if(response.data.status == '000'){
                        console.log(response.data.url);
                        window.location.href = response.data.url;
                        // head(response.data.url);
                    }
                });
            }
        }
    }
</script>