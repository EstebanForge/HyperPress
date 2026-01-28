(function () {
  if (window.HyperFieldsConditional) {
    return;
  }

  /**
   * HyperFields Conditional Logic Handler
   *
   * Handles field-level visibility based on conditional_logic property.
   * Monitors form changes and shows/hides fields dynamically.
   */
  class HyperFieldsConditional {
  constructor() {
    this.fields = new Map();
    this.init();
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this.setup());
    } else {
      this.setup();
    }
  }

  setup() {
    // Find all fields with conditional logic
    const conditionalFields = document.querySelectorAll(
      "[data-hyperpress-conditional-logic]",
    );

    conditionalFields.forEach((fieldWrapper) => {
      try {
        const logicData = JSON.parse(
          fieldWrapper.getAttribute("data-hyperpress-conditional-logic"),
        );
        const fieldName = this.getFieldName(fieldWrapper);

        if (fieldName && logicData) {
          this.fields.set(fieldName, {
            element: fieldWrapper,
            logic: logicData,
          });
        }
      } catch (e) {
        console.warn("Invalid conditional logic data:", e);
      }
    });

    // Set up event listeners for form changes
    this.setupEventListeners();

    // Initial visibility check
    this.evaluateAllFields();
  }

  getFieldName(fieldWrapper) {
    // Try to find the field name from input elements
    const input = fieldWrapper.querySelector("input, select, textarea");
    if (input && input.name) {
      // Extract field name from name attribute (e.g., "option_name[field_name]" -> "field_name")
      const match = input.name.match(/\[([^\]]+)\]$/);
      return match ? match[1] : input.name;
    }
    return null;
  }

  setupEventListeners() {
    // Listen for changes on all form inputs
    document.addEventListener("change", (e) => {
      if (e.target.matches("input, select, textarea")) {
        this.evaluateAllFields();
      }
    });

    // Also listen for input events for real-time updates
    document.addEventListener("input", (e) => {
      if (
        e.target.matches(
          'input[type="text"], input[type="email"], input[type="url"], textarea',
        )
      ) {
        this.evaluateAllFields();
      }
    });
  }

  evaluateAllFields() {
    this.fields.forEach((fieldData, fieldName) => {
      this.evaluateField(fieldName, fieldData);
    });
  }

  evaluateField(fieldName, fieldData) {
    const { element, logic } = fieldData;

    // Support format: { relation: 'AND', rules: [...] }
    // or legacy format: [condition1, condition2, ...]
    let relation = "AND";
    let conditions = logic;

    if (logic && typeof logic === "object" && logic.relation) {
      relation = logic.relation || "AND";
      conditions = logic.rules || logic;
    }

    if (!Array.isArray(conditions)) {
      conditions = [conditions];
    }

    // Evaluate each condition
    const results = conditions.map((condition) =>
      this.evaluateCondition(condition),
    );

    // Apply relation logic (AND/OR)
    let show = false;
    switch (relation.toUpperCase()) {
      case "OR":
        show = results.some((result) => result === true);
        break;
      case "AND":
      default:
        show = results.every((result) => result === true);
        break;
    }

    // Show or hide the field
    this.toggleField(element, show);
  }
  evaluateCondition(condition) {
    const { field, value, compare = "=", operator } = condition;

    // Support both 'compare' and 'operator' (our format)
    const op = compare || operator || "=";

    if (!field) {
      return false;
    }

    // Handle parent field references (parent.parent.field_name)
    let currentValue;
    if (field.startsWith("parent.")) {
      // TODO: Implement parent field references for complex/nested fields
      console.warn("Parent field references not yet implemented:", field);
      return false;
    } else {
      currentValue = this.getFieldValue(field);
    }

    return this.compareValues(currentValue, op, value);
  }

  compareValues(currentValue, operator, expectedValue) {
    switch (operator) {
      case "=":
      case "==":
        return this.isEqual(currentValue, expectedValue);
      case "!=":
        return !this.isEqual(currentValue, expectedValue);
      case ">":
        return parseFloat(currentValue) > parseFloat(expectedValue);
      case "<":
        return parseFloat(currentValue) < parseFloat(expectedValue);
      case ">=":
        return parseFloat(currentValue) >= parseFloat(expectedValue);
      case "<=":
        return parseFloat(currentValue) <= parseFloat(expectedValue);
      case "IN":
        return Array.isArray(expectedValue)
          ? expectedValue.some((val) => this.isEqual(currentValue, val))
          : false;
      case "NOT IN":
        return Array.isArray(expectedValue)
          ? !expectedValue.some((val) => this.isEqual(currentValue, val))
          : true;
      case "INCLUDES":
        return Array.isArray(currentValue)
          ? Array.isArray(expectedValue)
            ? expectedValue.every((val) => currentValue.indexOf(val) > -1)
            : currentValue.indexOf(expectedValue) > -1
          : String(currentValue).includes(String(expectedValue));
      case "EXCLUDES":
        return Array.isArray(currentValue)
          ? Array.isArray(expectedValue)
            ? expectedValue.every((val) => currentValue.indexOf(val) === -1)
            : currentValue.indexOf(expectedValue) === -1
          : !String(currentValue).includes(String(expectedValue));
      case "contains": // legacy support
        return String(currentValue).includes(String(expectedValue));
      case "in": // legacy support
        return Array.isArray(expectedValue)
          ? expectedValue.includes(currentValue)
          : false;
      default:
        return this.isEqual(currentValue, expectedValue);
    }
  }

  isEqual(currentValue, expectedValue) {
    // Handle boolean comparisons
    if (typeof expectedValue === "boolean") {
      if (typeof currentValue === "boolean") {
        return currentValue === expectedValue;
      }
      // Convert string/number to boolean for checkbox fields
      return (
        (currentValue === "1" ||
          currentValue === 1 ||
          currentValue === "true") === expectedValue
      );
    }

    // Handle array comparisons (for multiselect/checkbox arrays)
    if (Array.isArray(currentValue)) {
      return currentValue.includes(expectedValue);
    }

    // Standard string/number comparison
    return String(currentValue) === String(expectedValue);
  }

  getFieldValue(fieldName) {
    // Try different field name patterns
    const selectors = [
      `[name="${fieldName}"]`,
      `[name*="[${fieldName}]"]`,
      `input[name="${fieldName}"]`,
      `select[name="${fieldName}"]`,
      `textarea[name="${fieldName}"]`,
    ];

    for (const selector of selectors) {
      const element = document.querySelector(selector);
      if (element) {
        return this.extractValue(element);
      }
    }

    return null;
  }

  extractValue(element) {
    if (!element) {
      return null;
    }

    switch (element.type) {
      case "checkbox":
        return element.checked;
      case "radio":
        // For radio buttons, find the checked one
        const radioGroup = document.querySelectorAll(
          `input[name="${element.name}"]`,
        );
        for (const radio of radioGroup) {
          if (radio.checked) {
            return radio.value;
          }
        }
        return null;
      case "select-multiple":
        return Array.from(element.selectedOptions).map(
          (option) => option.value,
        );
      default:
        return element.value;
    }
  }

  toggleField(element, show) {
    if (show) {
      element.style.display = "";
      element.classList.remove("hyperpress-field-hidden");
    } else {
      element.style.display = "none";
      element.classList.add("hyperpress-field-hidden");
    }
  }
  }

  window.HyperFieldsConditional = HyperFieldsConditional;

  // Initialize when DOM is ready
  new HyperFieldsConditional();
})();
