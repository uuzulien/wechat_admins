<template>
    <div>
        <h2 style="font-weight: 300; margin-left: 20px; font-size: 22px;">公众号设置222</h2>

        <div style=" width: 400px">
            <div style=" height: 30px; line-height: 30px;text-align: center;color: white;background: black">
                <span>1.21 AM</span>
            </div>


            <div style="height: 60px;background: #353940;line-height: 60px;color: white;">
                <span style="font-size: 25px; float: left;">《返回</span>
                <span style="font-size: 25px; display:inline-block; width: 65%; text-align: center;">公众号名字</span>
                <span style="font-size: 25px; margin-left: 10px">xx</span>
            </div>

            <div style="height: 550px; border: solid 1px #cccccc;position:relative;">

                <div style="position: absolute;left: 0;bottom: 0; background: #cccccc">
                    <div style="cursor:pointer;height:60px; border: solid 1px #8c939d;font-size: 50px; width: 50px;float: left">
                        ⌨️
                    </div>
                    <div style="width: 396px;text-align:center;font-size: 50px;height:60px;border:solid 1px #8c939d;">
                        <div style="font-size:30px;cursor:pointer;width: 114px;border-right:solid 1px red;height: 60px; line-height:60px;float: left;">菜单</div>
                        <div style="font-size:30px;cursor:pointer;width: 114px;border-right:solid 1px red;height: 60px; line-height:60px;float: left;">菜单</div>
                        <div style="font-size:30px;cursor:pointer;width: 114px;height: 60px; line-height:60px;float: left;">菜单</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
    *{
        font-size: 13px;
    }
</style>
<script>
    export default {
        name: 'App',
        provide () {    //父组件中通过provide来提供变量，在子组件中通过inject来注入变量。
            return {
                reload: this.reload
            }
        },
        data() {
            return{
                isRouterAlive: true                    //控制视图是否显示的变量
            }
        },
        methods: {
            reload () {
                this.isRouterAlive = false;            //先关闭，
                this.$nextTick(function () {
                    this.isRouterAlive = true;         //再打开
                })
            }
        },
    }
</script>
