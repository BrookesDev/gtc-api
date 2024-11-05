<style>
    .input-row {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-evenly;
        padding: 5px;
    }

    .checkbox,
    .radio {
        width: 30px;
        height: 30px;
        position: relative;
        display: block;
    }

    .check,
    .rad {
        position: absolute;
        height: 100%;
        width: 100%;
        background-color: #e3eefa;
    }

    .check {
        border-radius: 3px;
        transition: all 0.4s;
    }

    .rad {
        border-radius: 50%;
        transition: all 0.4s;
    }

    .checkbox.on .check,
    .radio.on .rad {
        background-color: #04b439;
    }

    .checkbox.on .checked-icon,
    .radio.on .rad-icon {
        opacity: 1;
        text-align: center;
        animation-name: eh;
        animation-duration: 0.3s;
    }

    .checkbox .checked-icon,
    .radio .rad-icon {
        transition: opacity 0.3s ease-out;
    }

    .toggle {
        position: relative;
        width: 60px;
        height: 34px;
        display: inline-block;
    }

    .toggle .slider, #checkAll {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        transition: 0.4s;
        border-radius: 34px;
    }

    .toggle .slider:before, #checkAll:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
        box-shadow: 0px 0px 5px 2px rgba(0, 0, 0, 0.05);
    }

    .toggle .slider, #checkAll {
        background-color: #e3eefa;
    }

    .toggle.on .slider, .toggle.on #checkAll {
        background-color: #22ac2e;
    }

    .toggle.on .slider:before, .toggle.on #checkAll:before {
        transform: translateX(26px);
        box-shadow: 0px 0px 5px 2px rgba(0, 0, 0, 0.2);
    }
</style>
