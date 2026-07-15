<template>
    <div class="row">
        <div class="col-1">
            <button class="btn btn-secondary button" @click="add">Add</button>
        </div>

        <div class="col-7">
            <h3>Draggable {{ draggingInfo }}</h3>

            <draggable tag="ul" :list="list" class="list-group" handle=".handle">
                <template #item="{ element, index }">
                    <div class="list-group-item">
                        <i class="fa fa-align-justify handle"></i>

                        <span class="text">{{ element.name }}</span>

                        <input type="text" class="form-control input-test" v-model="element.text" />

                        <i class="fa fa-times close" @click="removeAt(index)"></i>
                    </div>
                </template>
            </draggable>
        </div>


        <pre>{{ list }}</pre>
    </div>
</template>

<script>
let id = 3;
import draggable from 'vuedraggable';

export default {
    name: "HandleDraggable",
    components: {
        draggable
    },
    data() {
        return {
            list: [
                { name: "John", text: "", id: 0 },
                { name: "Joao", text: "", id: 1 },
                { name: "Jean", text: "", id: 2 }
            ],
            dragging: false
        };
    },
    computed: {
        draggingInfo() {
            return this.dragging ? "under drag" : "";
        }
    },
    methods: {
        removeAt(idx) {
            this.list.splice(idx, 1);
        },
        add() {
            id++;
            this.list.push({ name: "Juan " + id, id, text: "" });
        }
    }
};
</script>

<style scoped>
.button-test {
    margin-top: 35px;
}
.handle-test {
    float: left;
    padding-top: 8px;
    padding-bottom: 8px;
    cursor: grab;
}
.close-test {
    float: right;
    padding-top: 8px;
    padding-bottom: 8px;
    cursor: pointer;
}
.input-test {
    display: inline-block;
    width: 50%;
}
.text-test {
    margin: 20px;
}
</style>
