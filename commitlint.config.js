const WIP_REGEX = /^wip[:]?$/i;
const RULE_ERROR_LEVEL = 2;
const HEADER_MAX_LENGTH = 150;
const SUBJECT_MIN_LENGTH = 5;

module.exports = {
    extends: ["@commitlint/config-conventional"],
    rules: {
        "custom-subject-empty": [RULE_ERROR_LEVEL, "never"],
        "custom-type-enum": [RULE_ERROR_LEVEL, "always"],
        "custom-no-wip": [RULE_ERROR_LEVEL, "always"],
        "custom-no-wip-subject": [RULE_ERROR_LEVEL, "always"],
        "subject-min-length": [RULE_ERROR_LEVEL, "always", SUBJECT_MIN_LENGTH],
        "subject-case": [0], // optional: allow flexibility in subject case
        "header-max-length": [RULE_ERROR_LEVEL, "always", HEADER_MAX_LENGTH]
    },
    plugins: [
        {
            rules: {
                "custom-subject-empty": ({ subject }) =>
                    subject && subject.trim().length > 0
                        ? [true]
                        : [
                            false,
                            "The commit needs a description after the colon (:). Eg: feat: add new feature"
                        ],
                "custom-type-enum": ({ type }) => {
                    const allowedTypes = [
                        "feat",
                        "fix",
                        "hotfix",
                        "docs",
                        "style",
                        "refactor",
                        "test",
                        "chore"
                    ];

                    if (!type) {
                        return [
                            false,
                            "Missing type. Use: feat, fix, chore, refactor, etc."
                        ];
                    }

                    if (!allowedTypes.includes(type)) {
                        return [
                            false,
                            `Type "${type} is invalid". Allowed types: ${allowedTypes.join(
                                ", "
                            )}`
                        ];
                    }

                    return [true];
                },
                "custom-no-wip": ({ header }) => {
                    const isWipOnly = WIP_REGEX.test(header.trim());
                    return [
                        !isWipOnly,
                        "Commit message cannot be just \"WIP\" (use a descriptive message)."
                    ];
                },
                "custom-no-wip-subject": ({ subject }) => {
                    if (!subject) return [true];

                    const isWipOnly = WIP_REGEX.test(subject.trim());
                    return [
                        !isWipOnly,
                        "Subject cannot be just \"WIP\". Use a descriptive message like \"implement user login\" instead."
                    ];
                }
            }
        }
    ]
};
