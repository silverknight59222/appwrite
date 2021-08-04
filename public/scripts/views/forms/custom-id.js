(function (window) {
  "use strict";
  window.ls.container.get("view").add({
    selector: "data-custom-id",
    controller: function (element, sdk, console) {
      let prevData = "";
      let idType = element.dataset["id-type"];

      const div = window.document.createElement("div");
      div.className = "input-copy";

      const button = window.document.createElement("i");
      button.type = "button";
      button.style.cursor = "pointer";

      const writer = document.createElement("input");
      writer.type = "text";
      writer.className = "";
      writer.setAttribute("maxlength", element.getAttribute("maxlength"));
      const placeholder = element.getAttribute("placeholder");
      if (placeholder) {
        writer.setAttribute("placeholder", placeholder);
      }

      const info = document.createElement("div");
      info.className = "text-fade text-size-xs margin-top-negative-small margin-bottom";

      div.appendChild(writer);
      div.appendChild(button);
      element.parentNode.insertBefore(div, element);
      element.parentNode.insertBefore(info, div.nextSibling);

      const switchType = function (event) {
        if (idType == "custom") {
          idType = "auto";
          setIdType(idType);
        } else {
          idType = "custom";
          setIdType(idType);
        }
      }

      const validate = function (event) {
        const [service, method] = element.dataset["validator"].split('.');
        const value = event.target.value;
        if (value.length < 1) {
          event.target.setCustomValidity("ID is required");
        } else {
          switch(service) {
            case 'projects':
              if (method == 'getPlatform') {
                const projectId = element.form.elements.namedItem("projectId").value;
                setValidity(console[service][method](projectId, value), event.target);
              } else {
                setValidity(console[service][method](value), event.target);
              }
              break;
            case 'teams':
              if (method == 'getMembership') {
                const teamId = element.form.elements.namedItem("teamId").value;
                setValidity(sdk[service][method](teamId, value), event.target);
              } else {
                setValidity(sdk[service][method](teamId, value), event.target);
              }
              break;
            default:
              setValidity(sdk[service][method](value), event.target);
          }
        }
      }

      const setValidity = async function (promise, target) {
        try {
          await promise;
          target.setCustomValidity("ID already exists");
        } catch (e) {
          target.setCustomValidity("");
        }
      }

      const setIdType = function (idType) {
        element.setAttribute("data-id-type", idType);
        if (idType == "custom") {
          info.innerHTML = "Allowed Characters A-Z, a-z, 0-9, and non-leading underscore";
          writer.value = prevData;
          writer.disabled = false;
          element.value = prevData;
          writer.focus();
          writer.addEventListener('blur', validate);
        } else {
          info.innerHTML = "Appwrite will generate a unique ID";
          prevData = writer.value;
          writer.disabled = true;
          writer.value = 'auto-generated';
          element.value = 'unique()';
        }
        button.className = idType == "custom" ? "icon-shuffle copy" : "icon-edit copy";
      }

      const syncEditorWithID = function (event) {
        if (element.value !== 'unique()') {
          writer.value = element.value;
        }
      }

      syncEditorWithID();
      setIdType(idType);
      writer.addEventListener("change", function (event) {
        element.value = writer.value;
      });
      writer.addEventListener('keypress', keypress);
      button.addEventListener("click", switchType);

    }
  });
})(window);
