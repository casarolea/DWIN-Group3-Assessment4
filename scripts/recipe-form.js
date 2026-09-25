/* behaviour for create-recipe.html */
(function () {
  "use strict";

  const STORAGE_KEY = "recipes";
  const MAX_PHOTO = 4 * 1024 * 1024;
  const MIN_PHOTO = 2 * 1024 * 1024;
  const PHOTO_DEFAULT_MSG = "Images cannot exceed 4MB.";
  const NUTRITION = ["servingSize", "energy", "calories", "protein", "fatTotal",
                     "fatSaturated", "carbs", "sugars", "fibre", "sodium"];

  const $ = (id) => document.getElementById(id);
  const form = $("recipe-form");
  const field = (name) => form.elements[name];
  const typeSingle = $("type-single");
  const typeMulti = $("type-multi");
  const partsEl = $("parts");
  const addPartBtn = $("add-part");
  const description = $("description");
  const remaining = $("remaining");
  const status = $("status");

  const photoInput = $("photo");
  const photoName = $("photo-name");
  const uploadBtn = $("upload-btn");
  const photoMsg = $("photo-msg");
  const photoPreview = $("photo-preview");
  let photoReady = false;
  let photoFileName = "";
  let photoUrl = "";
  let uid = 0;

  description.addEventListener("input", () => {
    remaining.textContent = 300 - description.value.length;
  });

  function showError(id, show) {
    $(id).hidden = !show;
  }

  /* ---------- parts, ingredients, method ---------- */

  function ingredientRow() {
    const row = document.createElement("div");
    row.className = "recipe-row";
    row.innerHTML =
      '<input type="text" class="form-control" data-kind="ingredient" aria-label="Ingredient" placeholder="eg. 2 cups plain flour">' +
      '<button type="button" class="recipe-remove" aria-label="Remove ingredient">&times;</button>';
    row.querySelector("button").addEventListener("click", () => row.remove());
    return row;
  }

  function renumberSteps(list) {
    list.querySelectorAll(".step-label").forEach((l, i) => (l.textContent = "Step " + (i + 1)));
  }

  function stepRow() {
    const id = "step-" + ++uid;
    const wrap = document.createElement("div");
    wrap.className = "recipe-step";
    wrap.innerHTML =
      '<div class="recipe-step-head">' +
        '<label class="step-label" for="' + id + '"></label>' +
        '<button type="button" class="recipe-remove" aria-label="Remove step">&times;</button>' +
      '</div>' +
      '<textarea id="' + id + '" class="form-control" rows="3" data-kind="method" placeholder="Describe this step"></textarea>';
    wrap.querySelector("button").addEventListener("click", () => {
      const list = wrap.parentElement;
      wrap.remove();
      renumberSteps(list);
    });
    return wrap;
  }

  function addPart() {
    const part = document.createElement("div");
    part.className = "recipe-part";
    part.innerHTML =
      '<input type="text" class="form-control part-name" placeholder="Part name, eg. Cake" aria-label="Part name" hidden>' +
      '<div class="recipe-part-cols">' +
        '<div>' +
          '<h3>Ingredients *</h3><div class="ingredients"></div>' +
          '<p class="recipe-hint">Enter one ingredient at a time</p>' +
          '<button type="button" class="btn btn-outline-recipe btn-sm add-ing">+ Add Ingredient</button>' +
        '</div>' +
        '<div>' +
          '<h3>Method *</h3><div class="method"></div>' +
          '<p class="recipe-hint">Enter one step at a time</p>' +
          '<button type="button" class="btn btn-outline-recipe btn-sm add-step">+ Add Step</button>' +
        '</div>' +
      '</div>' +
      '<div class="recipe-part-actions"><button type="button" class="btn btn-outline-recipe btn-sm remove-part" hidden>Remove this part</button></div>';

    const ing = part.querySelector(".ingredients");
    const met = part.querySelector(".method");
    ing.append(ingredientRow(), ingredientRow());
    met.append(stepRow());
    renumberSteps(met);

    part.querySelector(".add-ing").addEventListener("click", () => {
      const r = ingredientRow();
      ing.append(r);
      r.querySelector("input").focus();
    });
    part.querySelector(".add-step").addEventListener("click", () => {
      const r = stepRow();
      met.append(r);
      renumberSteps(met);
      r.querySelector("textarea").focus();
    });
    part.querySelector(".remove-part").addEventListener("click", () => {
      part.remove();
      syncType();
    });

    partsEl.append(part);
    syncType();
  }

  function syncType() {
    const multi = typeMulti.checked;
    const count = partsEl.children.length;
    addPartBtn.hidden = !multi;
    partsEl.querySelectorAll(".part-name").forEach((el) => (el.hidden = !multi));
    partsEl.querySelectorAll(".remove-part").forEach((el) => (el.hidden = !(multi && count > 1)));
  }

  [typeSingle, typeMulti].forEach((radio) =>
    radio.addEventListener("change", () => {
      if (typeSingle.checked && partsEl.children.length > 1) {
        if (!confirm("Switching to a single part recipe removes the extra parts. Continue?")) {
          typeMulti.checked = true;
          return;
        }
        Array.from(partsEl.children).slice(1).forEach((p) => p.remove());
      }
      syncType();
    })
  );
  addPartBtn.addEventListener("click", addPart);

  const values = (part, kind) =>
    Array.from(part.querySelectorAll('[data-kind="' + kind + '"]'))
      .map((i) => i.value.trim())
      .filter(Boolean);

  /* ---------- photo ---------- */

  function setPhotoMsg(text, isError) {
    photoMsg.textContent = text;
    photoMsg.classList.toggle("recipe-error", isError);
    photoMsg.classList.toggle("recipe-hint", !isError);
  }

  function photoProblem(file) {
    const validType = file.type === "image/jpeg" || file.type === "image/png";
    const validName = /\.(jpe?g|png)$/i.test(file.name);
    if (!validType || !validName) {
      return "Images must be JPG, JPEG or PNG format.";
    }
    if (file.size > MAX_PHOTO) return "Images cannot exceed 4MB.";
    return "";
  }

  function clearPhoto() {
    if (photoUrl) URL.revokeObjectURL(photoUrl);
    photoUrl = "";
    photoReady = false;
    photoFileName = "";
    photoPreview.hidden = true;
    photoPreview.removeAttribute("src");
  }

  photoInput.addEventListener("change", () => {
    clearPhoto();
    const file = photoInput.files[0];
    if (!file) {
      photoName.textContent = "No file chosen";
      uploadBtn.disabled = true;
      setPhotoMsg(PHOTO_DEFAULT_MSG, false);
      return;
    }
    photoName.textContent = file.name;
    const problem = photoProblem(file);
    uploadBtn.disabled = Boolean(problem);
    setPhotoMsg(problem || PHOTO_DEFAULT_MSG, Boolean(problem));
  });

  uploadBtn.addEventListener("click", () => {
    const file = photoInput.files[0];
    if (!file) return;
    const problem = photoProblem(file);
    if (problem) return setPhotoMsg(problem, true);
    clearPhoto();
    photoUrl = URL.createObjectURL(file);
    photoPreview.src = photoUrl;
    photoPreview.hidden = false;
    photoReady = true;
    photoFileName = file.name;
    setPhotoMsg(
      file.size < MIN_PHOTO
        ? "Photo added. Images under 2MB may look low quality in your printed cookbook."
        : "Photo added.",
      false
    );
  });

  /* ---------- my recipes list ---------- */

  function loadRecipes() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
    } catch (err) {
      return [];
    }
  }

  function storeRecipes(list) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
      return true;
    } catch (err) {
      return false;
    }
  }

  function renderRecipes() {
    const recipes = loadRecipes();
    const list = $("recipes-list");
    list.innerHTML = "";
    $("recipes-empty").hidden = recipes.length > 0;

    for (let i = recipes.length - 1; i >= 0; i--) {
      const r = recipes[i];
      const li = document.createElement("li");
      li.className = "recipe-item";

      const body = document.createElement("div");
      const h = document.createElement("h3");
      h.textContent = r.title || "Untitled recipe";
      body.append(h);

      const meta = [
        r.category,
        r.cuisine,
        r.difficulty && "Difficulty: " + r.difficulty,
        r.serves && "Serves " + r.serves,
        r.prep && "Prep " + r.prep,
        r.cook && "Cook " + r.cook,
      ].filter(Boolean).join(", ");
      if (meta) {
        const m = document.createElement("p");
        m.className = "recipe-meta";
        m.textContent = meta;
        body.append(m);
      }
      if (r.description) {
        const d = document.createElement("p");
        d.textContent = r.description;
        body.append(d);
      }

      const del = document.createElement("button");
      del.type = "button";
      del.className = "btn btn-outline-recipe btn-sm";
      del.textContent = "Delete";
      del.addEventListener("click", () => {
        if (!confirm('Delete "' + (r.title || "this recipe") + '"?')) return;
        const all = loadRecipes();
        all.splice(i, 1);
        storeRecipes(all);
        renderRecipes();
      });

      li.append(body, del);
      list.append(li);
    }
  }

  /* ---------- reset, cancel, save ---------- */

  function resetForm() {
    form.reset();
    partsEl.innerHTML = "";
    addPart();
    syncType();
    remaining.textContent = "300";
    clearPhoto();
    photoName.textContent = "No file chosen";
    uploadBtn.disabled = true;
    setPhotoMsg(PHOTO_DEFAULT_MSG, false);
    ["title-error", "category-error", "parts-error"].forEach((id) => showError(id, false));
    $("summary-error").hidden = true;
    status.textContent = "";
  }

  $("cancel-btn").addEventListener("click", () => {
    if (confirm("Cancel this recipe? Anything you've entered will be cleared.")) resetForm();
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const title = field("title").value.trim();
    const category = field("category").value;

    let partsBad = null;
    Array.from(partsEl.children).forEach((p) => {
      if (!values(p, "ingredient").length) {
        partsBad = partsBad || p.querySelector('[data-kind="ingredient"]') || p.querySelector(".add-ing");
      }
      if (!values(p, "method").length) {
        partsBad = partsBad || p.querySelector('[data-kind="method"]') || p.querySelector(".add-step");
      }
    });

        showError("title-error", !title);
    showError("category-error", !category);
    showError("parts-error", Boolean(partsBad));
    if (!photoReady) setPhotoMsg("Choose a JPG or PNG image and click Upload.", true);

    const problems = [];
    if (!title) problems.push("Enter a recipe title (Step 1).");
    if (!category) problems.push("Choose a category (Step 2).");
    if (partsBad) problems.push("Add at least one ingredient and one method step to every part (Step 3).");
    if (!photoReady) problems.push("Choose a photo and click Upload (Step 5).");

    const summary = $("summary-error");
    const summaryList = $("summary-error-list");
    if (problems.length) {
      summaryList.innerHTML = problems.map((p) => "<li>" + p + "</li>").join("");
      summary.hidden = false;
      summary.scrollIntoView({ behavior: "smooth", block: "start" });
    } else {
      summary.hidden = true;
    }

    if (!title) return field("title").focus();
    if (!category) return field("category").focus();
    if (partsBad) return partsBad.focus();
    if (!photoReady) return photoInput.focus();

    const nutrition = {};
    NUTRITION.forEach((n) => (nutrition[n] = field(n).value.trim()));

    const recipe = {
      title: title,
      description: field("description").value.trim(),
      category: category,
      cuisine: field("cuisine").value,
      difficulty: field("difficulty").value,
      serves: field("serves").value,
      prep: field("prep").value.trim(),
      marinate: field("marinate").value.trim(),
      cook: field("cook").value.trim(),
      makes: field("makes").value.trim(),
      multipart: typeMulti.checked,
      parts: Array.from(partsEl.children).map((p) => ({
        name: p.querySelector(".part-name").value.trim(),
        ingredients: values(p, "ingredient"),
        method: values(p, "method"),
      })),
      tips: field("tips").value.trim(),
      nutrition: nutrition,
      photoName: photoFileName, // the image itself is too big for localStorage; needs a backend pls help
    };

    const all = loadRecipes();
    all.push(recipe);
    if (!storeRecipes(all)) {
      status.textContent = "Couldn't save this recipe in your browser. Try again.";
      return;
    }
    console.log("Recipe saved", recipe);
    resetForm();
    renderRecipes();
    status.textContent = "Recipe saved. You can see it under My Recipes below.";
  });

  /* ---------- start ---------- */
  addPart();
  renderRecipes();
})();
