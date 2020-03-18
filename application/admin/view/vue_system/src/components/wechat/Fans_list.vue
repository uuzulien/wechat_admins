<template>
    <el-tabs type="border-card">
        <el-tab-pane label="粉丝列表">
            <el-row>
                <el-col :span="4">
                    <el-input type="text" placeholder="请输入粉丝名称" @keyup.enter.native="get_list(keyword)" v-model="keyword" :rows="12" show-word-limit class="input-with-select">
                        <el-button slot="append" icon="search" @click="get_list(keyword)"></el-button>
                    </el-input>
                </el-col>
            </el-row>
            <el-table :data="tableData" height="" :span-method="objectSpanMethod" border style="width: 100%" max-height="900">
                <el-table-column label="头像" width="180">
                    <template slot-scope="scope">
                        <img :width="120" :height="120" :src="scope.row.headimgurl"/>
                    </template>
                </el-table-column>
                <el-table-column prop="nickname" label="名字" width="180"></el-table-column>
                <el-table-column prop="service_type_info" label="地区" width="180">
                    <template slot-scope="scope">
                        {{ scope.row.country }}&nbsp{{ scope.row.province }}&nbsp{{ scope.row.city }}
                    </template>
                </el-table-column>
                <el-table-column prop="tagid_list" label="标签" width="180"></el-table-column>
                <el-table-column prop="id" label="粉丝id" width="180"></el-table-column>
                <el-table-column prop="subscribe_time" label="关注时间"></el-table-column>
            </el-table>
            <el-col :span="6" :offset="18">
                <el-pagination @current-change="pageset" :total="page.total" :page-size="page.ep" :current-page="page.p" background layout="prev,pager,next,jumper"></el-pagination>
            </el-col>
        </el-tab-pane>
    </el-tabs>
</template>
<script>
    export default {
        data:function(){
            return {
                keyword:'',
                tableData:[],
                page:{
                    ep:10,
                    p:1,
                    pages:0,
                    total:0
                },
            }
        },
        created: function () {
            this.get_list('all');
        },
        methods:{

            objectSpanMethod({ row, column, rowIndex, columnIndex }) {
                if (columnIndex == 2) {
                    return {
                        rowspan: 2,
                        colspan: 1
                    };
                }
            },
            get_list(name){
                let self = this;
                let info = {};
                if(self.keyword){
                    info = {'name' : self.keyword};
                }
                if(name != 'all' && name != ''){
                    info = {'name' : name};
                }
                info.page = self.page.p;
                info.limit = self.page.ep;
                self.$ajax.post(self.$interfase.get_fans_list,info).then(function (response){
                    if(response.data.status == '000'){
                        console.log(response.data.data.list);
                        self.tableData = response.data.data.list;
                        self.page.total = response.data.data.total;
                    }
                });
            },
            pageset:function(val){
                if(this.page.p != val){
                    this.page.p = val;
                    this.get_list('all');
                }
            },
        }
    }
</script>